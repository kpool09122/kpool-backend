<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeleteRole;

use Source\Wiki\Principal\Application\Exception\CannotDeleteSystemRoleException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class DeleteRole implements DeleteRoleInterface
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    /**
     * @throws RoleNotFoundException
     * @throws CannotDeleteSystemRoleException
     */
    public function process(DeleteRoleInputPort $input): void
    {
        $role = $this->roleRepository->findById($input->roleIdentifier());

        if ($role === null) {
            throw new RoleNotFoundException();
        }

        if ($role->isSystemRole()) {
            throw new CannotDeleteSystemRoleException();
        }

        $this->roleRepository->delete($role);
    }
}
