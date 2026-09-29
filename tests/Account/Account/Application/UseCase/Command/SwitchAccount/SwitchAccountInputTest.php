<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\SwitchAccount;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountInput;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class SwitchAccountInputTest extends TestCase
{
    public function testConstructWithTargetDelegation(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $originalPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetDelegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());

        $input = new SwitchAccountInput(
            $identityIdentifier,
            $originalPrincipalIdentifier,
            $targetDelegationIdentifier,
        );

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
        $this->assertSame($originalPrincipalIdentifier, $input->originalPrincipalIdentifier());
        $this->assertSame($targetDelegationIdentifier, $input->targetDelegationIdentifier());
    }

    public function testConstructWithoutTargetDelegation(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $originalPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new SwitchAccountInput(
            $identityIdentifier,
            $originalPrincipalIdentifier,
            null,
        );

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
        $this->assertSame($originalPrincipalIdentifier, $input->originalPrincipalIdentifier());
        $this->assertNull($input->targetDelegationIdentifier());
    }
}
