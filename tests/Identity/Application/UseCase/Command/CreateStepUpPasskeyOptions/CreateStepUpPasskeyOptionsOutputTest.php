<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions\CreateStepUpPasskeyOptionsOutput;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

class CreateStepUpPasskeyOptionsOutputTest extends TestCase
{
    public function testItSerializesOptionsAndDefaultsToEmptyArray(): void
    {
        $output = new CreateStepUpPasskeyOptionsOutput();
        $this->assertSame([], $output->toArray());
        $output->setOptions(new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174001'), new WebAuthnOptions('{"challenge":"value"}'));
        $this->assertSame(['challengeKey' => '123e4567-e89b-72d3-a456-426614174001','options' => ['challenge' => 'value']], $output->toArray());
    }
}
