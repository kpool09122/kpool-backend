<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\UpdatePasskey;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskeyInput;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class UpdatePasskeyInputTest extends TestCase
{
    public function testItReturnsTheValuesProvidedAtConstruction(): void
    {
        $identityIdentifier = new IdentityIdentifier('01994e3a-a15e-72d3-a456-426614174000');
        $passkeyIdentifier = new PasskeyCredentialIdentifier('01994e3a-a15e-72d3-a456-426614174001');
        $displayName = new PasskeyDisplayName('MacBook Pro');

        $input = new UpdatePasskeyInput(
            $identityIdentifier,
            $passkeyIdentifier,
            $displayName,
        );

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
        $this->assertSame($passkeyIdentifier, $input->passkeyIdentifier());
        $this->assertSame($displayName, $input->displayName());
    }
}
