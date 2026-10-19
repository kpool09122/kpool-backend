<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Service;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Condition;
use Source\Account\Principal\Domain\ValueObject\ConditionClause;
use Source\Account\Principal\Domain\ValueObject\ConditionKey;
use Source\Account\Principal\Domain\ValueObject\ConditionOperator;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PolicyEvaluation;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Shared\Domain\ValueObject\AccountCategory;

readonly class PolicyEvaluator implements PolicyEvaluatorInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyRepositoryInterface $policyRepository,
    ) {
    }

    /**
     * @param array<string, PolicyEvaluation> $evaluations
     * @return array<string, bool>
     */
    public function evaluateMany(array $evaluations): array
    {
        $principalIdentifiers = [];
        foreach ($evaluations as $evaluation) {
            if ($evaluation->principal->status() === PrincipalStatus::ACTIVE && $evaluation->principalGroups === null) {
                $principalIdentifiers[(string) $evaluation->principal->principalIdentifier()] = $evaluation->principal->principalIdentifier();
            }
        }
        $persistedGroups = $principalIdentifiers === [] ? [] : $this->principalGroupRepository->findByPrincipalIds(array_values($principalIdentifiers));
        $groupsByEvaluation = [];
        $allGroups = [];
        foreach ($evaluations as $key => $evaluation) {
            $groups = $evaluation->principal->status() !== PrincipalStatus::ACTIVE ? [] : array_filter(
                $evaluation->principalGroups ?? $persistedGroups,
                static fn (PrincipalGroup $group): bool => (string) $group->accountIdentifier() === (string) $evaluation->resource->accountIdentifier()
                    && $group->hasMember($evaluation->principal->principalIdentifier()),
            );
            $groupsByEvaluation[$key] = $groups;
            foreach ($groups as $group) {
                $allGroups[] = $group;
            }
        }
        $statementsByRole = $this->collectStatementsByRole($allGroups);
        $results = [];
        foreach ($evaluations as $key => $evaluation) {
            $results[$key] = $evaluation->principal->status() === PrincipalStatus::ACTIVE && $this->evaluateStatements(
                $this->statementsForGroups($groupsByEvaluation[$key], $statementsByRole),
                $evaluation->action,
                $evaluation->resource,
            );
        }

        return $results;
    }

    /** @param PrincipalGroup[]|null $principalGroups */
    public function evaluate(
        Principal $principal,
        Action $action,
        Resource $resource,
        ?array $principalGroups = null,
    ): bool {
        if ($principal->status() !== PrincipalStatus::ACTIVE) {
            return false;
        }

        return $this->evaluateStatements($this->collectStatements($principal, $resource, $principalGroups), $action, $resource);
    }

    /** @param Statement[] $statements */
    private function evaluateStatements(array $statements, Action $action, Resource $resource): bool
    {
        $applicableStatements = $this->filterApplicable($statements, $action, $resource->type());

        // 条件に一致する明示的 Deny を優先する。
        foreach ($applicableStatements as $statement) {
            if ($statement->effect() === Effect::DENY) {
                if ($this->matchesCondition($statement->condition(), $resource)) {
                    return false;
                }
            }
        }

        // 条件に一致する明示的 Allow がある場合のみ許可する。
        foreach ($applicableStatements as $statement) {
            if ($statement->effect() === Effect::ALLOW) {
                if ($this->matchesCondition($statement->condition(), $resource)) {
                    return true;
                }
            }
        }

        return false; // 暗黙的 Deny
    }

    /**
     * @param PrincipalGroup[]|null $principalGroups
     * @return Statement[]
     */
    private function collectStatements(Principal $principal, Resource $resource, ?array $principalGroups): array
    {
        $principalGroups = $principalGroups === null ? $this->principalGroupRepository->findByAccountIdAndPrincipal(
            $resource->accountIdentifier(),
            $principal->principalIdentifier(),
        ) : array_filter($principalGroups, static fn (PrincipalGroup $group): bool => (string) $group->accountIdentifier() === (string) $resource->accountIdentifier() && $group->hasMember($principal->principalIdentifier()));

        return $this->statementsForGroups($principalGroups, $this->collectStatementsByRole($principalGroups));
    }

    /**
     * @param PrincipalGroup[] $principalGroups
     * @return array<string, Statement[]>
     */
    private function collectStatementsByRole(array $principalGroups): array
    {
        $roleIdentifiers = [];
        foreach ($principalGroups as $principalGroup) {
            foreach ($principalGroup->roles() as $roleIdentifier) {
                $roleIdentifiers[(string) $roleIdentifier] = $roleIdentifier;
            }
        }
        if ($roleIdentifiers === []) {
            return [];
        }
        $roles = $this->roleRepository->findByIds(array_values($roleIdentifiers));
        $policyIdentifiers = [];
        foreach ($roles as $role) {
            foreach ($role->policies() as $policyIdentifier) {
                $policyIdentifiers[(string) $policyIdentifier] = $policyIdentifier;
            }
        }
        $policiesById = [];
        foreach ($this->policyRepository->findByIds(array_values($policyIdentifiers)) as $policy) {
            $policiesById[(string) $policy->policyIdentifier()] = $policy;
        }
        $statementsByRole = [];
        foreach ($roles as $role) {
            $statements = [];
            foreach ($role->policies() as $policyIdentifier) {
                array_push($statements, ...(($policiesById[(string) $policyIdentifier] ?? null)?->statements() ?? []));
            }
            $statementsByRole[(string) $role->roleIdentifier()] = $statements;
        }

        return $statementsByRole;
    }

    /**
     * @param PrincipalGroup[] $principalGroups
     * @param array<string, Statement[]> $statementsByRole
     * @return Statement[]
     */
    private function statementsForGroups(array $principalGroups, array $statementsByRole): array
    {
        $roleIds = [];
        foreach ($principalGroups as $group) {
            foreach ($group->roles() as $roleIdentifier) {
                $roleIds[(string) $roleIdentifier] = true;
            }
        }
        $statements = [];
        foreach (array_keys($roleIds) as $roleId) {
            array_push($statements, ...($statementsByRole[$roleId] ?? []));
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
        if ($condition === null) {
            return true;
        }

        return array_all($condition->clauses(), fn (ConditionClause $clause): bool => $this->evaluateClause($clause, $resource));
    }

    private function evaluateClause(ConditionClause $clause, Resource $resource): bool
    {
        $resourceValue = $this->getResourceValue($clause->key(), $resource);

        return $this->compareValues($clause->operator(), $resourceValue, $clause->value());
    }

    private function getResourceValue(ConditionKey $key, Resource $resource): string|bool|null
    {
        return match ($key) {
            ConditionKey::RESOURCE_ACCOUNT_TYPE => $resource->accountType()?->value,
            ConditionKey::RESOURCE_ACCOUNT_CATEGORY => $resource->accountCategory()?->value,
            ConditionKey::AFFILIATION_REQUEST_PAIR_ALLOWED => $this->affiliationRequestPairAllowed($resource),
            ConditionKey::RESOURCE_DELEGATION_ID => $resource->delegationIdentifier() !== null
                ? (string) $resource->delegationIdentifier()
                : null,
            ConditionKey::RESOURCE_TARGET_ACCOUNT_ID => $resource->targetAccountIdentifier() !== null
                ? (string) $resource->targetAccountIdentifier()
                : null,
        };
    }

    /** @param string|bool|list<string> $conditionValue */
    private function compareValues(ConditionOperator $operator, string|bool|null $resourceValue, string|bool|array $conditionValue): bool
    {
        if ($resourceValue === null) {
            return false;
        }

        return match ($operator) {
            ConditionOperator::EQUALS => $resourceValue === $conditionValue,
            ConditionOperator::NOT_EQUALS => $resourceValue !== $conditionValue,
            ConditionOperator::IN => in_array($resourceValue, (array) $conditionValue, true),
            ConditionOperator::NOT_IN => ! in_array($resourceValue, (array) $conditionValue, true),
        };
    }

    private function affiliationRequestPairAllowed(Resource $resource): bool
    {
        return match ($resource->affiliationRequestingAccountCategory()) {
            AccountCategory::TALENT => $resource->accountCategory() === AccountCategory::AGENCY,
            AccountCategory::AGENCY => $resource->accountCategory() === AccountCategory::TALENT,
            default => false,
        };
    }
}
