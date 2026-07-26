<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use RuntimeException;
use Source\Account\Principal\Application\Exception\CannotRemoveLastOwnerException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class RemovePrincipalFromPrincipalGroup implements RemovePrincipalFromPrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
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

        $ownerRole = $this->roleRepository->findByName(Role::OWNER);
        if ($ownerRole === null) {
            throw new RuntimeException('Owner account role is not found.');
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

        $this->principalGroupRepository->save($principalGroup);

        $output->setPrincipalGroup($principalGroup);
    }
}
