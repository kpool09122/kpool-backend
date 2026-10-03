<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service\WebAuthn;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\StepUpAuthenticationChallenge;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class StepUpAuthenticationChallengeTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $key = new ChallengeSessionKey('019c9b4c-0000-7000-8000-000000000001');
        $challenge = new WebAuthnChallenge('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $options = new WebAuthnOptions('{"challenge":"challenge"}');
        $expiresAt = new DateTimeImmutable('2026-10-03T01:02:03+00:00');
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new StepUpAuthenticationChallenge($key, $challenge, $options, $expiresAt, $identityIdentifier);

        $this->assertSame($key, $subject->key);
        $this->assertSame($challenge, $subject->challenge);
        $this->assertSame($options, $subject->options);
        $this->assertSame($expiresAt, $subject->expiresAt);
        $this->assertSame($identityIdentifier, $subject->identityIdentifier);
    }
}
