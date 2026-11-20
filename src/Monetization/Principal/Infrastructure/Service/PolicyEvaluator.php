<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Infrastructure\Service;

use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Condition;
use Source\Monetization\Principal\Domain\ValueObject\ConditionClause;
use Source\Monetization\Principal\Domain\ValueObject\ConditionKey;
use Source\Monetization\Principal\Domain\ValueObject\ConditionOperator;
use Source\Monetization\Principal\Domain\ValueObject\Effect;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Principal\Domain\ValueObject\Statement;

readonly class PolicyEvaluator implements PolicyEvaluatorInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyRepositoryInterface $policyRepository,
    ) {
    }

    /** @param array<PrincipalGroup>|null $principalGroups */
    public function evaluate(Principal $principal, Action $action, Resource $resource, ?array $principalGroups = null): bool
    {
        if ($principal->status() !== PrincipalStatus::ACTIVE || (string) $resource->accountIdentifier() !== (string) $principal->accountIdentifier()) {
            return false;
        }
        $statements = $this->collectStatements($principal, $principalGroups);
        $applicableStatements = $this->filterApplicable($statements, $action, $resource->type());

        // 明示的 Deny を優先する。
        foreach ($applicableStatements as $statement) {
            if ($statement->effect() === Effect::DENY && $this->matchesCondition($statement->condition(), $resource)) {
                return false;
            }
        }

        // 条件に一致する明示的 Allow がある場合のみ許可する。
        foreach ($applicableStatements as $statement) {
            if ($statement->effect() === Effect::ALLOW && $this->matchesCondition($statement->condition(), $resource)) {
                return true;
            }
        }

        return false; // 暗黙的 Deny
    }

    /** @param array<PrincipalGroup>|null $principalGroups
     * @return Statement[] */
    private function collectStatements(Principal $principal, ?array $principalGroups = null): array
    {
        // 1. Principal が所属する PrincipalGroup を取得
        $principalGroups = $principalGroups === null
            ? $this->principalGroupRepository->findByPrincipalId($principal->principalIdentifier())
            : array_filter($principalGroups, static fn (PrincipalGroup $group): bool => $group->hasMember($principal->principalIdentifier()));

        if (empty($principalGroups)) {
            return [];
        }

        // 2. 全ての RoleIdentifier を収集
        $allRoleIdentifiers = [];
        foreach ($principalGroups as $principalGroup) {
            if ((string) $principalGroup->accountIdentifier() !== (string) $principal->accountIdentifier()) {
                continue;
            }
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
            if ($role->accountIdentifier() !== null && (string) $role->accountIdentifier() !== (string) $principal->accountIdentifier()) {
                continue;
            }
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
            if ($policy->accountIdentifier() !== null && (string) $policy->accountIdentifier() !== (string) $principal->accountIdentifier()) {
                continue;
            }
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

    private function matchesCondition(?Condition $condition, Resource $resource): bool
    {
        return $condition === null || array_all(
            $condition->clauses(),
            fn (ConditionClause $clause): bool => $this->evaluateClause($clause, $resource),
        );
    }

    private function evaluateClause(ConditionClause $clause, Resource $resource): bool
    {
        $resourceValue = match ($clause->key()) {
            ConditionKey::RESOURCE_ACCOUNT_ID => (string) $resource->accountIdentifier(),
        };
        $conditionValue = $clause->value();

        return match ($clause->operator()) {
            ConditionOperator::EQUALS => $resourceValue === $conditionValue,
            ConditionOperator::NOT_EQUALS => $resourceValue !== $conditionValue,
            ConditionOperator::IN => in_array($resourceValue, (array) $conditionValue, true),
            ConditionOperator::NOT_IN => ! in_array($resourceValue, (array) $conditionValue, true),
        };
    }
}
