<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Command\RequestDelegation;

use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Application\UseCase\Command\RequestDelegation\RequestDelegationInput;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class RequestDelegationInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $principal = new Principal(new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001'), new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001'), new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'));
        $targetAccountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new RequestDelegationInput($principal, $targetAccountIdentifier);

        $this->assertSame($principal, $subject->principal());
        $this->assertSame($targetAccountIdentifier, $subject->targetAccountIdentifier());
    }
}
