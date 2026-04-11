<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreateRole;

use Source\Wiki\Principal\Domain\Factory\RoleFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class CreateRole implements CreateRoleInterface
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private RoleFactoryInterface $roleFactory,
    ) {
    }

    public function process(CreateRoleInputPort $input, CreateRoleOutputPort $output): void
    {
        $role = $this->roleFactory->create(
            $input->name(),
            $input->policies(),
            $input->isSystemRole(),
        );

        $this->roleRepository->save($role);

        $output->setRole($role);
    }
}
