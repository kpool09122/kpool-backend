<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole\ChangeCollaboratorRoleOutput;

class ChangeCollaboratorRoleOutputTest extends TestCase
{
    public function testReportsWhetherMembershipChanged(): void
    {
        $output = new ChangeCollaboratorRoleOutput();
        self::assertFalse($output->changed());
        $output->setChanged(true);
        self::assertTrue($output->changed());
        $output->setChanged(false);
        self::assertFalse($output->changed());
    }
}
