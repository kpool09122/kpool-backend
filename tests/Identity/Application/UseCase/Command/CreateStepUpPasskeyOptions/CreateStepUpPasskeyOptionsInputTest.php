<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions\CreateStepUpPasskeyOptionsInput;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class CreateStepUpPasskeyOptionsInputTest extends TestCase
{
    public function testItPreservesIdentityInstance(): void
    {
        $identity = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $this->assertSame($identity, (new CreateStepUpPasskeyOptionsInput($identity))->identityIdentifier());
    }
}
