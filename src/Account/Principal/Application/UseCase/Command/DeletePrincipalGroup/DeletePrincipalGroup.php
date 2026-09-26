<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup;

use Source\Account\Principal\Application\Exception\CannotDeleteDefaultPrincipalGroupException;
use Source\Account\Principal\Application\Exception\CannotDeleteLastOwnerGroupException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class DeletePrincipalGroup implements DeletePrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyRepositoryInterface $policyRepository,
    ) {
    }

    /**
     * @throws PrincipalGroupNotFoundException
     * @throws CannotDeleteDefaultPrincipalGroupException
     * @throws CannotDeleteLastOwnerGroupException
     */
    public function process(DeletePrincipalGroupInputPort $input): void
    {
        $principalGroup = $this->principalGroupRepository->findById($input->principalGroupIdentifier());

        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException();
        }

        if ($principalGroup->isDefault()) {
            throw new CannotDeleteDefaultPrincipalGroupException();
        }

        $ownerRole = $this->roleRepository->findSystemByName(Role::OWNER);
        if ($ownerRole === null) {
            throw SystemRoleNotFoundException::owner();
        }

        if ($principalGroup->hasRole($ownerRole->roleIdentifier()) && $principalGroup->memberCount() > 0) {
            $allPrincipalGroups = $this->principalGroupRepository->findByAccountId($principalGroup->accountIdentifier());

            $ownerMemberCount = 0;
            foreach ($allPrincipalGroups as $principalGroupInAccount) {
                if ($principalGroupInAccount->hasRole($ownerRole->roleIdentifier())) {
                    $ownerMemberCount += $principalGroupInAccount->memberCount();
                }
            }

            if ($ownerMemberCount <= $principalGroup->memberCount()) {
                throw new CannotDeleteLastOwnerGroupException();
            }
        }

        $delegationRoles = [];
        $delegationPolicies = [];
        if ($principalGroup->delegationIdentifier() !== null) {
            $delegationRoles = $this->roleRepository->findByIds($principalGroup->roles());
            foreach ($delegationRoles as $role) {
                foreach ($this->policyRepository->findByIds($role->policies()) as $policy) {
                    $delegationPolicies[(string) $policy->policyIdentifier()] = $policy;
                }
            }
        }

        $this->principalGroupRepository->delete($principalGroup);

        foreach ($delegationRoles as $role) {
            $this->roleRepository->delete($role);
        }
        foreach ($delegationPolicies as $policy) {
            $this->policyRepository->delete($policy);
        }
    }
}
