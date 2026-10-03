<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\DeleteRole;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteRole\DeleteRoleInput;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

class DeleteRoleInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $roleIdentifier = new RoleIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new DeleteRoleInput($roleIdentifier);

        $this->assertSame($roleIdentifier, $subject->roleIdentifier());
    }
}
