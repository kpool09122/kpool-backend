<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\WebAuthn;

use DateTimeImmutable;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyUserHandle;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Shared\Domain\ValueObject\Email;

readonly class RegistrationChallenge
{
    public function __construct(
        public ChallengeSessionKey $key,
        public WebAuthnChallenge $challenge,
        public WebAuthnOptions $options,
        public DateTimeImmutable $expiresAt,
        public PasskeyUserHandle $userHandle,
        public Email $email,
        public SignupSession $signupSession,
    ) {
    }
}
