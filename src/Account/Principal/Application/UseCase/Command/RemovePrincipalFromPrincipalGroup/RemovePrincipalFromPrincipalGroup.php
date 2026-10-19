<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use Source\Account\Principal\Application\Exception\CannotRemoveLastOwnerException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\Service\PrincipalGroupChangeGuardInterface;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class RemovePrincipalFromPrincipalGroup implements RemovePrincipalFromPrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
        private PrincipalGroupChangeGuardInterface $principalGroupChangeGuard,
    ) {
    }

    /**
     * @throws PrincipalGroupNotFoundException
     * @throws CannotRemoveLastOwnerException
     */
    public function process(RemovePrincipalFromPrincipalGroupInputPort $input, RemovePrincipalFromPrincipalGroupOutputPort $output): void
    {
        $principalGroup = $this->principalGroupRepository->findById($input->principalGroupIdentifier());

        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException();
        }

        $ownerRole = $this->roleRepository->findSystemByName(Role::OWNER);
        if ($ownerRole === null) {
            throw SystemRoleNotFoundException::owner();
        }

        if ($principalGroup->hasRole($ownerRole->roleIdentifier())) {
            $allPrincipalGroups = $this->principalGroupRepository->findByAccountId($principalGroup->accountIdentifier());

            $totalOwnerCount = 0;
            foreach ($allPrincipalGroups as $principalGroupInAccount) {
                if ($principalGroupInAccount->hasRole($ownerRole->roleIdentifier())) {
                    $totalOwnerCount += $principalGroupInAccount->memberCount();
                }
            }

            if ($totalOwnerCount <= 1) {
                throw new CannotRemoveLastOwnerException();
            }
        }

        $principalGroup->removeMember($input->principalIdentifier());

        $groups = $allPrincipalGroups ?? $this->principalGroupRepository->findByAccountId($principalGroup->accountIdentifier());
        foreach ($groups as $index => $group) {
            if ((string) $group->principalGroupIdentifier() === (string) $principalGroup->principalGroupIdentifier()) {
                $groups[$index] = $principalGroup;
            }
        }
        $this->principalGroupChangeGuard->assertPreserved($principalGroup->accountIdentifier(), $groups);

        $this->principalGroupRepository->save($principalGroup);

        $output->setPrincipalGroup($principalGroup);
    }
}
