<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service\WebAuthn;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Application\Service\WebAuthn\RegistrationChallenge;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\OneTimeToken;

class RegistrationChallengeTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $key = new ChallengeSessionKey('019c9b4c-0000-7000-8000-000000000001');
        $challenge = new WebAuthnChallenge('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $options = new WebAuthnOptions('{"challenge":"challenge"}');
        $expiresAt = new DateTimeImmutable('2026-10-03T01:02:03+00:00');
        $passkeyUserIdentifier = new PasskeyUserIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $email = new Email('user@example.com');
        $signupSession = new SignupSession(AccountType::CORPORATION, new OneTimeToken(str_repeat('a', 64)), 'returnTo-value');

        $subject = new RegistrationChallenge($key, $challenge, $options, $expiresAt, $passkeyUserIdentifier, $email, $signupSession);

        $this->assertSame($key, $subject->key);
        $this->assertSame($challenge, $subject->challenge);
        $this->assertSame($options, $subject->options);
        $this->assertSame($expiresAt, $subject->expiresAt);
        $this->assertSame($passkeyUserIdentifier, $subject->passkeyUserIdentifier);
        $this->assertSame($email, $subject->email);
        $this->assertSame($signupSession, $subject->signupSession);
    }
}
