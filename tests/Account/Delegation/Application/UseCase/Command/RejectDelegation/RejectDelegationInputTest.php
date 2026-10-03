<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Command\RejectDelegation;

use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Application\UseCase\Command\RejectDelegation\RejectDelegationInput;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class RejectDelegationInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $delegationIdentifier = new DelegationIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $principal = new Principal(new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001'), new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001'), new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'));

        $subject = new RejectDelegationInput($delegationIdentifier, $principal);

        $this->assertSame($delegationIdentifier, $subject->delegationIdentifier());
        $this->assertSame($principal, $subject->principal());
    }
}
