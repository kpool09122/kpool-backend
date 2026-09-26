<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail;

use LogicException;
use Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail\VerifyPasskeyRecoveryEmailOutput;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Tests\TestCase;

class VerifyPasskeyRecoveryEmailOutputTest extends TestCase
{
    public function testItSerializesTheRecoveryKey(): void
    {
        $output = new VerifyPasskeyRecoveryEmailOutput();
        $output->setRecoveryKey(new PasskeyRecoveryKey('123e4567-e89b-72d3-a456-426614174001'));
        $this->assertSame(['recoveryKey' => '123e4567-e89b-72d3-a456-426614174001'], $output->toArray());
    }

    public function testItRejectsSerializationBeforeAKeyIsSet(): void
    {
        $this->expectException(LogicException::class);
        (new VerifyPasskeyRecoveryEmailOutput())->toArray();
    }
}
