<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole;

use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\Grading\Domain\ValueObject\CollaboratorRole;

interface ChangeCollaboratorRoleInputPort
{
    public function email(): Email;

    public function targetRole(): CollaboratorRole;
}
