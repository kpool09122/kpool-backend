<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole;

class ChangeCollaboratorRoleOutput implements ChangeCollaboratorRoleOutputPort
{
    private bool $changed = false;

    public function setChanged(bool $changed): void
    {
        $this->changed = $changed;
    }

    public function changed(): bool
    {
        return $this->changed;
    }
}
