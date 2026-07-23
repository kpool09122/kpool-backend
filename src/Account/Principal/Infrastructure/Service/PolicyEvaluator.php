<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Service;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Statement;

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
        $applicableStatements = $this->filterApplicable($statements, $action, $resource->type());

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
            $principal,
        );

        if (empty($principalGroups)) {
            return [];
        }

        $accountRoles = [];
        foreach ($principalGroups as $principalGroup) {
            $accountRoles[$principalGroup->role()->value] = $principalGroup->role();
        }

        $roles = $this->roleRepository->findByRoles(array_values($accountRoles));
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
    private function filterApplicable(array $statements, Action $action, ResourceType $resourceType): array
    {
        return array_filter($statements, static function (Statement $statement) use ($action, $resourceType): bool {
            $actionMatches = in_array($action, $statement->actions(), true);
            $resourceMatches = in_array($resourceType, $statement->resourceTypes(), true);

            return $actionMatches && $resourceMatches;
        });
    }
}
