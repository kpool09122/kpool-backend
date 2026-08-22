<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Service;

use Source\Account\Principal\Domain\Entity\Principal;
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
use Source\Account\Principal\Domain\ValueObject\Resource;
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

    public function evaluate(
        Principal $principal,
        Action $action,
        Resource $resource,
    ): bool {
        $statements = $this->collectStatements($principal, $resource);
        $applicableStatements = $this->filterApplicable($statements, $action, $resource);

        foreach ($applicableStatements as $statement) {
            if ($statement->effect() === Effect::DENY) {
                return false;
            }
        }

        foreach ($applicableStatements as $statement) {
            if ($statement->effect() === Effect::ALLOW) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Statement[]
     */
    private function collectStatements(Principal $principal, Resource $resource): array
    {
        $principalGroups = $this->principalGroupRepository->findByAccountIdAndPrincipal(
            $resource->accountIdentifier(),
            $principal->principalIdentifier(),
        );

        if (empty($principalGroups)) {
            return [];
        }

        $roleIdentifiers = [];
        foreach ($principalGroups as $principalGroup) {
            foreach ($principalGroup->roles() as $roleIdentifier) {
                $roleIdentifiers[(string) $roleIdentifier] = $roleIdentifier;
            }
        }

        $roles = $this->roleRepository->findByIds(array_values($roleIdentifiers));
        $policyIdentifiers = [];
        foreach ($roles as $role) {
            foreach ($role->policies() as $policyIdentifier) {
                $policyIdentifiers[(string) $policyIdentifier] = $policyIdentifier;
            }
        }

        $policies = $this->policyRepository->findByIds(array_values($policyIdentifiers));

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
    private function filterApplicable(array $statements, Action $action, Resource $resource): array
    {
        return array_filter($statements, function (Statement $statement) use ($action, $resource): bool {
            $actionMatches = in_array($action, $statement->actions(), true);
            $resourceMatches = in_array($resource->type(), $statement->resourceTypes(), true);

            return $actionMatches && $resourceMatches && $this->conditionMatches($statement->condition(), $resource);
        });
    }

    private function conditionMatches(?Condition $condition, Resource $resource): bool
    {
        if ($condition === null) {
            return true;
        }

        foreach ($condition->clauses() as $clause) {
            if (! $this->conditionClauseMatches($clause, $resource)) {
                return false;
            }
        }

        return true;
    }

    private function conditionClauseMatches(ConditionClause $clause, Resource $resource): bool
    {
        $actual = $this->conditionActualValue($clause->key(), $resource);

        if ($actual === null) {
            return false;
        }

        return match ($clause->operator()) {
            ConditionOperator::EQUALS => $actual === $clause->value(),
            ConditionOperator::NOT_EQUALS => $actual !== $clause->value(),
            ConditionOperator::IN => in_array($actual, (array) $clause->value(), true),
            ConditionOperator::NOT_IN => ! in_array($actual, (array) $clause->value(), true),
        };
    }

    private function conditionActualValue(ConditionKey $key, Resource $resource): string|bool|null
    {
        return match ($key) {
            ConditionKey::RESOURCE_ACCOUNT_TYPE => $resource->accountType()?->value,
            ConditionKey::RESOURCE_ACCOUNT_CATEGORY => $resource->accountCategory()?->value,
            ConditionKey::AFFILIATION_REQUEST_PAIR_ALLOWED => $this->affiliationRequestPairAllowed($resource),
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
