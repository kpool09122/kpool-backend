<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions;

use LogicException;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions\CreatePasskeyRecoveryOptionsOutput;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Tests\TestCase;

class CreatePasskeyRecoveryOptionsOutputTest extends TestCase
{
    public function testItSerializesOptions(): void
    {
        $output = new CreatePasskeyRecoveryOptionsOutput();
        $output->setOptions(new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174002'), new WebAuthnOptions('{"challenge":"value"}'));
        $this->assertSame(['challengeKey' => '123e4567-e89b-72d3-a456-426614174002', 'options' => ['challenge' => 'value']], $output->toArray());
    }

    public function testItRejectsSerializationBeforeOptionsAreSet(): void
    {
        $this->expectException(LogicException::class);
        (new CreatePasskeyRecoveryOptionsOutput())->toArray();
    }
}
