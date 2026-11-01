<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole;

interface ChangeCollaboratorRoleInterface
{
    public function process(ChangeCollaboratorRoleInputPort $input, ChangeCollaboratorRoleOutputPort $output): void;
}
