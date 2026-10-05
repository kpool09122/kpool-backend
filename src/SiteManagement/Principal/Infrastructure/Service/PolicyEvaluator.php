<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Infrastructure\Service;

use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Condition;
use Source\SiteManagement\Principal\Domain\ValueObject\Effect;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Principal\Domain\ValueObject\Statement;

readonly class PolicyEvaluator implements PolicyEvaluatorInterface
{
    public function __construct(private PrincipalGroupRepositoryInterface $principalGroupRepository, private RoleRepositoryInterface $roleRepository, private PolicyRepositoryInterface $policyRepository)
    {
    }

    public function evaluate(Principal $principal, Action $action, Resource $resource): bool
    {
        $allowed = false;
        foreach ($this->collectStatements($principal) as $statement) {
            if (! in_array($action, $statement->actions(), true) || ! in_array($resource->type(), $statement->resourceTypes(), true)) {
                continue;
            }
            if ($statement->condition() === Condition::OWN_CONTACT && ($resource->type() !== ResourceType::CONTACT || $resource->ownerIdentityIdentifier() === null || (string) $resource->ownerIdentityIdentifier() !== (string) $principal->identityIdentifier())) {
                continue;
            }
            if ($statement->effect() === Effect::DENY) {
                return false;
            }
            $allowed = true;
        }

        return $allowed;
    }

    /** @return Statement[] */
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
}
