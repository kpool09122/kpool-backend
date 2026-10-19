<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Service;

use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalStatus;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;

readonly class PrincipalGroupManagerContinuity
{
    public function __construct(private RoleRepositoryInterface $roleRepository, private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    /** @param array<PrincipalGroup> $principalGroups
     * @param array<string, Principal> $principalsById
     * @param callable(Principal): bool $isReachable */
    public function hasManager(array $principalGroups, array $principalsById, callable $isReachable): bool
    {
        $administrator = $this->roleRepository->findSystemByName('Administrator');
        $operator = $this->roleRepository->findSystemByName('Operator');
        if ($administrator === null) {
            return false;
        }
        foreach ($principalGroups as $group) {
            if (! $group->hasRole($administrator->roleIdentifier()) || ($operator !== null && $group->hasRole($operator->roleIdentifier()))) {
                continue;
            }
            foreach ($group->members() as $identifier) {
                $principal = $principalsById[(string) $identifier] ?? null;
                if ($principal !== null && $principal->status() === PrincipalStatus::ACTIVE
                    && (string) $principal->accountIdentifier() === (string) $group->accountIdentifier()
                    && $isReachable($principal)
                    && $this->policyEvaluator->evaluate($principal, Action::PRINCIPAL_GROUP_MANAGE, new Resource(ResourceType::PRINCIPAL_GROUP), $principalGroups)) {
                    return true;
                }
            }
        }

        return false;
    }
}
