<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup;

use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class AttachRoleToPrincipalGroup implements AttachRoleToPrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    /**
     * @throws PrincipalGroupNotFoundException
     * @throws RoleNotFoundException
     */
    public function process(AttachRoleToPrincipalGroupInputPort $input): void
    {
        $principalGroup = $this->principalGroupRepository->findById($input->principalGroupIdentifier());

        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException();
        }

        $role = $this->roleRepository->findById($input->roleIdentifier());

        if ($role === null) {
            throw new RoleNotFoundException();
        }

        $principalGroup->addRole($input->roleIdentifier());

        $this->principalGroupRepository->save($principalGroup);
    }
}
