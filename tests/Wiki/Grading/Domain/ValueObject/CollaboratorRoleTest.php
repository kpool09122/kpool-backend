<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Grading\Domain\ValueObject\CollaboratorRole;

class CollaboratorRoleTest extends TestCase
{
    public function testTargetsMatchSeededSystemRoleNames(): void
    {
        self::assertSame(CollaboratorRole::COLLABORATOR, CollaboratorRole::from('Collaborator'));
        self::assertSame(CollaboratorRole::SENIOR_COLLABORATOR, CollaboratorRole::from('SeniorCollaborator'));
        self::assertNull(CollaboratorRole::tryFrom('Operator'));
    }
}
