<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PrincipalTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $principalIdentifier = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new Principal($principalIdentifier, $identityIdentifier, $accountIdentifier);

        $this->assertSame($principalIdentifier, $subject->principalIdentifier());
        $this->assertSame($identityIdentifier, $subject->identityIdentifier());
        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
    }
}
