<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions;

use Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions\CreatePasskeyRecoveryOptionsInput;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Tests\TestCase;

class CreatePasskeyRecoveryOptionsInputTest extends TestCase
{
    public function testItExposesTheRecoveryKey(): void
    {
        $key = new PasskeyRecoveryKey('123e4567-e89b-72d3-a456-426614174001');
        $this->assertSame($key, (new CreatePasskeyRecoveryOptionsInput($key))->recoveryKey());
    }
}
