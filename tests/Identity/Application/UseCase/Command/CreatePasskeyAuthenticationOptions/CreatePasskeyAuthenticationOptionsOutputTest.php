<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptionsOutput;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

class CreatePasskeyAuthenticationOptionsOutputTest extends TestCase
{
    public function testToArrayReturnsEmptyBeforeOptionsAreSet(): void
    {
        $output = new CreatePasskeyAuthenticationOptionsOutput();

        $this->assertSame([], $output->toArray());
    }

    public function testToArrayReturnsChallengeKeyAndOptions(): void
    {
        $output = new CreatePasskeyAuthenticationOptionsOutput();
        $output->setOptions(
            new ChallengeSessionKey('01994e3a-a15e-72d3-a456-426614174000'),
            new WebAuthnOptions('{"challenge":"challenge","rpId":"example.com","allowCredentials":[],"userVerification":"required","timeout":300000}'),
        );

        $this->assertSame([
            'challengeKey' => '01994e3a-a15e-72d3-a456-426614174000',
            'options' => [
                'challenge' => 'challenge',
                'rpId' => 'example.com',
                'allowCredentials' => [],
                'userVerification' => 'required',
                'timeout' => 300000,
            ],
        ], $output->toArray());
    }
}
