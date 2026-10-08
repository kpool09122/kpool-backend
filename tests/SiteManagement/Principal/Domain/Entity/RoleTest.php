<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Domain\Entity\Role;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

class RoleTest extends TestCase
{
    public function testEntityPreservesIdentityAndData(): void
    {
        $id = new RoleIdentifier('69200000-0000-7000-8000-000000000099');
        $entity = new Role($id, 'test', []);
        self::assertSame($id, $entity->roleIdentifier());
        self::assertSame('test', $entity->name());
        self::assertSame([], $entity->policies());
    }
}
