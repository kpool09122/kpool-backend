<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\DeletePasskey;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\DeletePasskey\DeletePasskeyInput;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class DeletePasskeyInputTest extends TestCase
{
    public function testItReturnsTheValuesProvidedAtConstruction(): void
    {
        $identityIdentifier = new IdentityIdentifier('01994e3a-a15e-72d3-a456-426614174000');
        $passkeyIdentifier = new PasskeyCredentialIdentifier('01994e3a-a15e-72d3-a456-426614174001');

        $input = new DeletePasskeyInput($identityIdentifier, $passkeyIdentifier);

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
        $this->assertSame($passkeyIdentifier, $input->passkeyIdentifier());
    }
}
