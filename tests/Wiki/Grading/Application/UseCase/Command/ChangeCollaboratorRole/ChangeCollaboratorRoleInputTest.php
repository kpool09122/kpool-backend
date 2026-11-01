<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole\ChangeCollaboratorRoleInput;
use Source\Wiki\Grading\Domain\ValueObject\CollaboratorRole;

class ChangeCollaboratorRoleInputTest extends TestCase
{
    public function testKeepsEmailAndExplicitTargetRole(): void
    {
        $email = new Email('collaborator@example.com');
        foreach (CollaboratorRole::cases() as $targetRole) {
            $input = new ChangeCollaboratorRoleInput($email, $targetRole);
            self::assertSame($email, $input->email());
            self::assertSame($targetRole, $input->targetRole());
        }
    }
}
