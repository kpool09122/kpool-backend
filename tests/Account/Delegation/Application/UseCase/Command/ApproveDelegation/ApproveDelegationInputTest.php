<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationInput;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveDelegationInputTest extends TestCase
{
    public function testCarriesDelegationAndAuthenticatedPrincipal(): void
    {
        $id = new DelegationIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $input = new ApproveDelegationInput($id, $principal);
        $this->assertSame($id, $input->delegationIdentifier());
        $this->assertSame($principal, $input->principal());
    }
}
