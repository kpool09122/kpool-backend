<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreateRole;

interface CreateRoleInterface
{
    public function process(CreateRoleInputPort $input, CreateRoleOutputPort $output): void;
}
