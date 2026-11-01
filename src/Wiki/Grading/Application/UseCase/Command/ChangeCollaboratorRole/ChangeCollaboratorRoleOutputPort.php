<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole;

interface ChangeCollaboratorRoleOutputPort
{
    public function setChanged(bool $changed): void;

    public function changed(): bool;
}
