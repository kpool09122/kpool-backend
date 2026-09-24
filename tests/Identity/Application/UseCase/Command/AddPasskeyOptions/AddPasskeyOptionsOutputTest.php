<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\AddPasskeyOptions;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\UseCase\Command\AddPasskeyOptions\AddPasskeyOptionsOutput;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

class AddPasskeyOptionsOutputTest extends TestCase
{
    public function testItIsEmptyBeforeOptionsAreSet(): void
    {
        $this->assertSame([], (new AddPasskeyOptionsOutput())->toArray());
    }

    public function testItReturnsChallengeKeyAndDecodedOptions(): void
    {
        $output = new AddPasskeyOptionsOutput();
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
