<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreatePasskeyOptions;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\CreatePasskeyOptions\CreatePasskeyOptionsInput;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class CreatePasskeyOptionsInputTest extends TestCase
{
    public function testItReturnsActorIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier('01994e3a-a15e-72d3-a456-426614174000');
        $input = new CreatePasskeyOptionsInput($identityIdentifier);

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
    }
}
