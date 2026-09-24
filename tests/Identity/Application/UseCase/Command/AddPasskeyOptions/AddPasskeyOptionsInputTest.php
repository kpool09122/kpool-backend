<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\AddPasskeyOptions;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\AddPasskeyOptions\AddPasskeyOptionsInput;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class AddPasskeyOptionsInputTest extends TestCase
{
    public function testItReturnsActorIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier('01994e3a-a15e-72d3-a456-426614174000');
        $input = new AddPasskeyOptionsInput($identityIdentifier);

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
    }
}
