<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsOutput;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

class CreatePasskeyRegistrationOptionsOutputTest extends TestCase
{
    public function testToArrayReturnsEmptyBeforeOptionsAreSet(): void
    {
        $output = new CreatePasskeyRegistrationOptionsOutput();

        $this->assertSame([], $output->toArray());
    }

    public function testToArrayReturnsChallengeKeyAndOptions(): void
    {
        $output = new CreatePasskeyRegistrationOptionsOutput();
        $output->setOptions(
            new ChallengeSessionKey('01994e3a-a15e-72d3-a456-426614174000'),
            new WebAuthnOptions('{"challenge":"challenge","timeout":300000}'),
        );

        $this->assertSame([
            'challengeKey' => '01994e3a-a15e-72d3-a456-426614174000',
            'options' => [
                'challenge' => 'challenge',
                'timeout' => 300000,
            ],
        ], $output->toArray());
    }
}
