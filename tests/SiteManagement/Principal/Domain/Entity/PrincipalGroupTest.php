<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;

class PrincipalGroupTest extends TestCase
{
    public function testEntityPreservesIdentityAndData(): void
    {
        $id = new PrincipalGroupIdentifier('69200000-0000-7000-8000-000000000099');
        $entity = new PrincipalGroup($id, 'test', [], new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        self::assertSame($id, $entity->principalGroupIdentifier());
        self::assertSame('test', $entity->name());
        self::assertSame([], $entity->members());
    }
}
