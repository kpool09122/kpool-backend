<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\Service\CurrentAccount;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class CurrentAccountTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $originalIdentityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $originalAccountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $originalPrincipalIdentifier = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $effectiveAccountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $effectivePrincipalIdentifier = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $delegationIdentifier = new DelegationIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new CurrentAccount($originalIdentityIdentifier, $originalAccountIdentifier, $originalPrincipalIdentifier, $effectiveAccountIdentifier, $effectivePrincipalIdentifier, $delegationIdentifier);

        $this->assertSame($originalIdentityIdentifier, $subject->originalIdentityIdentifier);
        $this->assertSame($originalAccountIdentifier, $subject->originalAccountIdentifier);
        $this->assertSame($originalPrincipalIdentifier, $subject->originalPrincipalIdentifier);
        $this->assertSame($effectiveAccountIdentifier, $subject->effectiveAccountIdentifier);
        $this->assertSame($effectivePrincipalIdentifier, $subject->effectivePrincipalIdentifier);
        $this->assertSame($delegationIdentifier, $subject->delegationIdentifier);
    }

    public function testAllowsAbsentOptionalValues(): void
    {
        $subject = new CurrentAccount(new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001'), new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'), new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001'), new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'), new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001'), null);
        $this->assertNull($subject->delegationIdentifier);
    }
}
