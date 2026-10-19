<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Service;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

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
                    && $principal->delegationIdentifier() === null
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
