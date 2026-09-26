<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptionsInput;
use Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptionsInputPort;

class CreatePasskeyAuthenticationOptionsInputTest extends TestCase
{
    public function testItImplementsInputPortWithoutRequiringIdentityInformation(): void
    {
        $input = new CreatePasskeyAuthenticationOptionsInput();

        $this->assertInstanceOf(CreatePasskeyAuthenticationOptionsInputPort::class, $input);
    }
}
