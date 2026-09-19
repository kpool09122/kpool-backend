<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\Redis;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Domain\Entity\ChallengeSession;
use Source\Identity\Domain\Exception\ChallengeSessionIdentityMismatchException;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Exception\ChallengeSessionPurposeMismatchException;
use Source\Identity\Domain\ValueObject\ChallengePurpose;
use Source\Identity\Domain\ValueObject\ChallengeSessionIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyRegistrationContext;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\OneTimeToken;

class ChallengeSessionStorageService implements ChallengeSessionStorageServiceInterface
{
    private const string KEY_PREFIX = 'webauthn_challenge:';

    public function store(ChallengeSession $session): void
    {
        $ttl = $session->expiresAt()->getTimestamp() - time();
        if ($ttl <= 0) {
            throw new ChallengeSessionNotFoundException('Challenge session has already expired.');
        }

        $registrationContext = $session->registrationContext();
        Redis::setex(
            $this->key($session->identifier()),
            $ttl,
            json_encode([
                'challenge' => (string) $session->challenge(),
                'purpose' => $session->purpose()->value,
                'options' => $session->options(),
                'expires_at' => $session->expiresAt()->format(DATE_ATOM),
                'identity_id' => $session->identityIdentifier() !== null
                    ? (string) $session->identityIdentifier()
                    : null,
                'registration_context' => $registrationContext === null ? null : [
                    'email' => (string) $registrationContext->email(),
                    'account_type' => $registrationContext->signupSession()->accountType()?->value,
                    'one_time_token' => $registrationContext->signupSession()->oneTimeToken() !== null
                        ? (string) $registrationContext->signupSession()->oneTimeToken()
                        : null,
                    'return_to' => $registrationContext->signupSession()->returnTo(),
                ],
            ], JSON_THROW_ON_ERROR),
        );
    }

    public function consume(
        ChallengeSessionIdentifier $identifier,
        ChallengePurpose $expectedPurpose,
        ?IdentityIdentifier $expectedIdentityIdentifier = null,
    ): ChallengeSession {
        $raw = Redis::command('GETDEL', [$this->key($identifier)]);
        if (! is_string($raw)) {
            throw new ChallengeSessionNotFoundException();
        }

        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data) || ! isset($data['purpose'], $data['challenge'], $data['options'], $data['expires_at'])) {
            throw new ChallengeSessionNotFoundException();
        }
        $purpose = ChallengePurpose::tryFrom((string) $data['purpose']);
        if ($purpose !== $expectedPurpose) {
            throw new ChallengeSessionPurposeMismatchException();
        }
        $identityIdentifier = isset($data['identity_id']) && is_string($data['identity_id'])
            ? new IdentityIdentifier($data['identity_id'])
            : null;
        if ($expectedIdentityIdentifier !== null && (string) $identityIdentifier !== (string) $expectedIdentityIdentifier) {
            throw new ChallengeSessionIdentityMismatchException();
        }
        $expiresAt = new DateTimeImmutable((string) $data['expires_at']);
        if ($expiresAt <= new DateTimeImmutable()) {
            throw new ChallengeSessionNotFoundException();
        }

        return new ChallengeSession(
            $identifier,
            new WebAuthnChallenge((string) $data['challenge']),
            $purpose,
            (string) $data['options'],
            $expiresAt,
            $identityIdentifier,
            $this->registrationContext($data['registration_context'] ?? null),
        );
    }

    private function key(ChallengeSessionIdentifier $identifier): string
    {
        return self::KEY_PREFIX . $identifier;
    }

    /** @param mixed $data */
    private function registrationContext(mixed $data): ?PasskeyRegistrationContext
    {
        if (! is_array($data) || ! isset($data['email'])) {
            return null;
        }
        $accountType = isset($data['account_type']) && is_string($data['account_type'])
            ? AccountType::tryFrom($data['account_type'])
            : null;
        $oneTimeToken = isset($data['one_time_token']) && is_string($data['one_time_token'])
            ? new OneTimeToken($data['one_time_token'])
            : null;

        return new PasskeyRegistrationContext(
            new Email((string) $data['email']),
            new SignupSession(
                $accountType,
                $oneTimeToken,
                isset($data['return_to']) && is_string($data['return_to']) ? $data['return_to'] : null,
            ),
        );
    }
}
