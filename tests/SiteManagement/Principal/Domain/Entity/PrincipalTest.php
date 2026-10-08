<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

class PrincipalTest extends TestCase
{
    public function testEntityPreservesIdentityAndData(): void
    {
        $id = new PrincipalIdentifier('69200000-0000-7000-8000-000000000099');
        $entity = new Principal($id, new IdentityIdentifier('69200000-0000-7000-8000-000000000099'), new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        self::assertSame($id, $entity->principalIdentifier());
        self::assertSame('69200000-0000-7000-8000-000000000099', (string) $entity->identityIdentifier());
    }
}
