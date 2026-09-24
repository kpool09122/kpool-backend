<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\AddPasskeyOptions;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\AddPasskeyOptions\AddPasskeyOptionsInput;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class AddPasskeyOptionsInputTest extends TestCase
{
    public function testItReturnsActorIdentityAndDelegationContext(): void
    {
        $identityIdentifier = new IdentityIdentifier('01994e3a-a15e-72d3-a456-426614174000');
        $delegationIdentifier = new DelegationIdentifier('01994e3a-a15e-72d3-a456-426614174001');
        $originalIdentityIdentifier = new IdentityIdentifier('01994e3a-a15e-72d3-a456-426614174002');
        $input = new AddPasskeyOptionsInput(
            $identityIdentifier,
            $delegationIdentifier,
            $originalIdentityIdentifier,
        );

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
        $this->assertSame($delegationIdentifier, $input->delegationIdentifier());
        $this->assertSame($originalIdentityIdentifier, $input->originalIdentityIdentifier());
    }
}
