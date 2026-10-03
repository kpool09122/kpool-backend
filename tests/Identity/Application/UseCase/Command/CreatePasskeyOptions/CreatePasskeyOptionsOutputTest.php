<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreatePasskeyOptions;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\UseCase\Command\CreatePasskeyOptions\CreatePasskeyOptionsOutput;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

class CreatePasskeyOptionsOutputTest extends TestCase
{
    public function testItIsEmptyBeforeOptionsAreSet(): void
    {
        $this->assertSame([], (new CreatePasskeyOptionsOutput())->toArray());
    }

    public function testItReturnsChallengeKeyAndDecodedOptions(): void
    {
        $output = new CreatePasskeyOptionsOutput();
        $output->setOptions(
            new ChallengeSessionKey('01994e3a-a15e-72d3-a456-426614174000'),
            new WebAuthnOptions('{"challenge":"challenge"}'),
        );

        $this->assertSame([
            'challengeKey' => '01994e3a-a15e-72d3-a456-426614174000',
            'options' => ['challenge' => 'challenge'],
        ], $output->toArray());
    }
}
