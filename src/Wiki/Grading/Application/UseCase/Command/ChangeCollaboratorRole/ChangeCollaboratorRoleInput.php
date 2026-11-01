<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole;

use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\Grading\Domain\ValueObject\CollaboratorRole;

readonly class ChangeCollaboratorRoleInput implements ChangeCollaboratorRoleInputPort
{
    public function __construct(private Email $email, private CollaboratorRole $targetRole)
    {
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function targetRole(): CollaboratorRole
    {
        return $this->targetRole;
    }
}
