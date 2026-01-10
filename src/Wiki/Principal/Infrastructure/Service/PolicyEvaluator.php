<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Service;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Principal\Domain\ValueObject\Condition;
use Source\Wiki\Principal\Domain\ValueObject\ConditionClause;
use Source\Wiki\Principal\Domain\ValueObject\ConditionKey;
use Source\Wiki\Principal\Domain\ValueObject\ConditionOperator;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class PolicyEvaluator implements PolicyEvaluatorInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyRepositoryInterface $policyRepository,
    ) {
    }

    public function evaluate(
        Principal $principal,
        Action $action,
        ResourceIdentifier $resource,
    ): bool {
        // PrincipalGroup → Role → Policy の階層を辿って Statement を収集
        $statements = $this->collectStatements($principal);

        // 該当するStatementを抽出（action + resourceType一致）
        $applicableStatements = $this->filterApplicable($statements, $action, $resource->type());

        // Deny優先チェック
        foreach ($applicableStatements as $statement) {
            if ($statement->effect() === Effect::DENY) {
                if ($this->matchesCondition($statement->condition(), $resource, $principal)) {
                    return false; // 明示的Deny
                }
            }
        }

        // Allowチェック
        foreach ($applicableStatements as $statement) {
            if ($statement->effect() === Effect::ALLOW) {
                if ($this->matchesCondition($statement->condition(), $resource, $principal)) {
                    return true; // 明示的Allow
                }
            }
        }

        return false; // 暗黙的Deny
    }

    /**
     * Principal が所属する PrincipalGroup から Statement を収集.
     *
     * N+1 問題を回避するため、Role と Policy を一括取得する.
     *
     * @return Statement[]
     */
    private function collectStatements(Principal $principal): array
    {
        // 1. Principal が所属する PrincipalGroup を取得
        $principalGroups = $this->principalGroupRepository->findByPrincipalId($principal->principalIdentifier());

        if (empty($principalGroups)) {
            return [];
        }

        // 2. 全ての RoleIdentifier を収集
        $allRoleIdentifiers = [];
        foreach ($principalGroups as $principalGroup) {
            foreach ($principalGroup->roles() as $roleIdentifier) {
                $allRoleIdentifiers[(string) $roleIdentifier] = $roleIdentifier;
            }
        }

        if (empty($allRoleIdentifiers)) {
            return [];
        }

        // 3. Role を一括取得
        $roles = $this->roleRepository->findByIds(array_values($allRoleIdentifiers));

        // 4. 全ての PolicyIdentifier を収集
        $allPolicyIdentifiers = [];
        foreach ($roles as $role) {
            foreach ($role->policies() as $policyIdentifier) {
                $allPolicyIdentifiers[(string) $policyIdentifier] = $policyIdentifier;
            }
        }

        if (empty($allPolicyIdentifiers)) {
            return [];
        }

        // 5. Policy を一括取得
        $policies = $this->policyRepository->findByIds(array_values($allPolicyIdentifiers));

        // 6. Statement を収集
        $statements = [];
        foreach ($policies as $policy) {
            array_push($statements, ...$policy->statements());
        }

        return $statements;
    }

    /**
     * @param Statement[] $statements
     * @return Statement[]
     */
    private function filterApplicable(array $statements, Action $action, ResourceType $resourceType): array
    {
        return array_filter($statements, static function (Statement $statement) use ($action, $resourceType): bool {
            $actionMatches = in_array($action, $statement->actions(), true);
            $resourceMatches = in_array($resourceType, $statement->resourceTypes(), true);

            return $actionMatches && $resourceMatches;
        });
    }

    /**
     * Condition を評価する.
     *
     * @param Condition|null $condition null の場合は制約なし（true）
     */
    private function matchesCondition(?Condition $condition, ResourceIdentifier $resource, Principal $principal): bool
    {
        if ($condition === null) {
            return true;
        }

        // 全ての Clause が AND で評価される
        foreach ($condition->clauses() as $clause) {
            if (! $this->evaluateClause($clause, $resource, $principal)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 単一の ConditionClause を評価する.
     */
    private function evaluateClause(ConditionClause $clause, ResourceIdentifier $resource, Principal $principal): bool
    {
        // リソースの値を取得
        $resourceValue = $this->getResourceValue($clause->key(), $resource);

        // 条件値を解決（変数の場合は Principal から値を取得）
        $conditionValue = $this->resolveConditionValue($clause->value(), $principal);

        // 演算子に応じて評価
        return $this->compareValues($clause->operator(), $resourceValue, $conditionValue);
    }

    /**
     * リソースから条件キーに対応する値を取得.
     *
     * @return string|string[]|null
     */
    private function getResourceValue(ConditionKey $key, ResourceIdentifier $resource): string|array|null
    {
        return match ($key) {
            ConditionKey::RESOURCE_AGENCY_ID => $resource->agencyId(),
            ConditionKey::RESOURCE_GROUP_ID => $resource->groupIds(),
            ConditionKey::RESOURCE_TALENT_ID => $resource->talentIds(),
            ConditionKey::RESOURCE_IS_OFFICIAL => null, // ResourceIdentifier には isOfficial が含まれていない
        };
    }

    /**
     * 条件値を解決する（変数の場合は Principal から値を取得）.
     *
     * @return string|string[]|bool|null
     */
    private function resolveConditionValue(ConditionValue|string|bool $value, Principal $principal): string|array|bool|null
    {
        if ($value instanceof ConditionValue) {
            return match ($value) {
                ConditionValue::PRINCIPAL_AGENCY_ID => $principal->agencyId(),
                ConditionValue::PRINCIPAL_WIKI_GROUP_IDS => $principal->groupIds(),
                ConditionValue::PRINCIPAL_TALENT_IDS => $principal->talentIds(),
            };
        }

        return $value;
    }

    /**
     * 演算子に応じて値を比較する.
     *
     * @param string|string[]|bool|null $resourceValue
     * @param string|string[]|bool|null $conditionValue
     */
    private function compareValues(
        ConditionOperator $operator,
        string|array|bool|null $resourceValue,
        string|array|bool|null $conditionValue,
    ): bool {
        return match ($operator) {
            ConditionOperator::EQUALS => $this->evaluateEquals($resourceValue, $conditionValue),
            ConditionOperator::NOT_EQUALS => $this->evaluateNotEquals($resourceValue, $conditionValue),
            ConditionOperator::IN => $this->evaluateIn($resourceValue, $conditionValue),
            ConditionOperator::NOT_IN => $this->evaluateNotIn($resourceValue, $conditionValue),
        };
    }

    /**
     * EQUALS 演算子を評価.
     *
     * resource:agencyId eq ${principal.agencyId} のような比較
     *
     * @param string|string[]|bool|null $resourceValue
     * @param string|string[]|bool|null $conditionValue
     */
    private function evaluateEquals(string|array|bool|null $resourceValue, string|array|bool|null $conditionValue): bool
    {
        if ($resourceValue === null || $conditionValue === null) {
            return false;
        }

        // 配列同士の比較は空でない交差があるかチェック
        if (is_array($resourceValue) && is_array($conditionValue)) {
            return count(array_intersect($resourceValue, $conditionValue)) > 0;
        }

        // スカラー同士の比較
        if (! is_array($resourceValue) && ! is_array($conditionValue)) {
            return $resourceValue === $conditionValue;
        }

        // 型が異なる場合は false
        return false;
    }

    /**
     * NOT_EQUALS 演算子を評価.
     *
     * @param string|string[]|bool|null $resourceValue
     * @param string|string[]|bool|null $conditionValue
     */
    private function evaluateNotEquals(string|array|bool|null $resourceValue, string|array|bool|null $conditionValue): bool
    {
        return ! $this->evaluateEquals($resourceValue, $conditionValue);
    }

    /**
     * IN 演算子を評価.
     *
     * resource:groupId in ${principal.wikiGroupIds} のような比較
     * リソースの groupIds のいずれかが Principal の groupIds に含まれているかチェック
     *
     * @param string|string[]|bool|null $resourceValue
     * @param string|string[]|bool|null $conditionValue
     */
    private function evaluateIn(string|array|bool|null $resourceValue, string|array|bool|null $conditionValue): bool
    {
        if ($resourceValue === null || $conditionValue === null) {
            return false;
        }

        // conditionValue が配列でない場合は配列に変換
        $conditionArray = is_array($conditionValue) ? $conditionValue : [$conditionValue];

        if (empty($conditionArray)) {
            return false;
        }

        // resourceValue が配列の場合、いずれかが conditionArray に含まれているかチェック
        if (is_array($resourceValue)) {
            if (empty($resourceValue)) {
                return false;
            }

            return count(array_intersect($resourceValue, $conditionArray)) > 0;
        }

        // resourceValue がスカラーの場合、conditionArray に含まれているかチェック
        return in_array($resourceValue, $conditionArray, true);
    }

    /**
     * NOT_IN 演算子を評価.
     *
     * @param string|string[]|bool|null $resourceValue
     * @param string|string[]|bool|null $conditionValue
     */
    private function evaluateNotIn(string|array|bool|null $resourceValue, string|array|bool|null $conditionValue): bool
    {
        return ! $this->evaluateIn($resourceValue, $conditionValue);
    }
}
