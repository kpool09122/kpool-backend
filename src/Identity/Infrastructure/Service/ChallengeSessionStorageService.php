<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\Redis;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AdditionChallenge;
use Source\Identity\Application\Service\WebAuthn\AuthenticationChallenge;
use Source\Identity\Application\Service\WebAuthn\RegistrationChallenge;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\Exception\ChallengeSessionIdentityMismatchException;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Exception\ChallengeSessionPurposeMismatchException;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\OneTimeToken;

class ChallengeSessionStorageService implements ChallengeSessionStorageServiceInterface
{
    private const string KEY_PREFIX = 'webauthn_challenge:';
    private const string REGISTRATION = 'registration';
    private const string AUTHENTICATION = 'authentication';
    private const string ADDITION = 'addition';

    public function storeRegistration(RegistrationChallenge $challenge): void
    {
        $signupSession = $challenge->signupSession;
        $this->store(
            $challenge->key,
            $challenge->challenge,
            $challenge->options,
            $challenge->expiresAt,
            self::REGISTRATION,
            [
                'passkey_user_id' => (string) $challenge->passkeyUserIdentifier,
                'email' => (string) $challenge->email,
                'account_type' => $signupSession->accountType()?->value,
                'one_time_token' => $signupSession->oneTimeToken() !== null
                    ? (string) $signupSession->oneTimeToken()
                    : null,
                'return_to' => $signupSession->returnTo(),
            ],
        );
    }

    public function consumeRegistration(ChallengeSessionKey $key): RegistrationChallenge
    {
        $data = $this->consume($key, self::REGISTRATION);
        if (! isset($data['passkey_user_id'], $data['email'])) {
            throw new ChallengeSessionNotFoundException();
        }

        return new RegistrationChallenge(
            $key,
            new WebAuthnChallenge($data['challenge']),
            new WebAuthnOptions($data['options']),
            new DateTimeImmutable($data['expires_at']),
            new PasskeyUserIdentifier($data['passkey_user_id']),
            new Email($data['email']),
            $this->signupSession($data),
        );
    }

    public function storeAuthentication(AuthenticationChallenge $challenge): void
    {
        $this->store(
            $challenge->key,
            $challenge->challenge,
            $challenge->options,
            $challenge->expiresAt,
            self::AUTHENTICATION,
        );
    }

    public function consumeAuthentication(ChallengeSessionKey $key): AuthenticationChallenge
    {
        $data = $this->consume($key, self::AUTHENTICATION);

        return new AuthenticationChallenge(
            $key,
            new WebAuthnChallenge($data['challenge']),
            new WebAuthnOptions($data['options']),
            new DateTimeImmutable($data['expires_at']),
        );
    }

    public function storeAddition(AdditionChallenge $challenge): void
    {
        $this->store(
            $challenge->key,
            $challenge->challenge,
            $challenge->options,
            $challenge->expiresAt,
            self::ADDITION,
            ['identity_id' => (string) $challenge->identityIdentifier],
        );
    }

    public function consumeAddition(
        ChallengeSessionKey $key,
        IdentityIdentifier $expectedIdentityIdentifier,
    ): AdditionChallenge {
        $data = $this->consume($key, self::ADDITION);
        if (! isset($data['identity_id'])) {
            throw new ChallengeSessionNotFoundException();
        }
        $identityIdentifier = new IdentityIdentifier($data['identity_id']);
        if ((string) $identityIdentifier !== (string) $expectedIdentityIdentifier) {
            throw new ChallengeSessionIdentityMismatchException();
        }

        return new AdditionChallenge(
            $key,
            new WebAuthnChallenge($data['challenge']),
            new WebAuthnOptions($data['options']),
            new DateTimeImmutable($data['expires_at']),
            $identityIdentifier,
        );
    }

    /** @param array<string, string|null> $context */
    private function store(
        ChallengeSessionKey $key,
        WebAuthnChallenge $challenge,
        WebAuthnOptions $options,
        DateTimeImmutable $expiresAt,
        string $purpose,
        array $context = [],
    ): void {
        $ttl = $expiresAt->getTimestamp() - time();
        if ($ttl <= 0) {
            throw new ChallengeSessionNotFoundException('Challenge session has already expired.');
        }

        Redis::setex(
            $this->redisKey($key),
            $ttl,
            json_encode([
                'challenge' => (string) $challenge,
                'purpose' => $purpose,
                'options' => $options->json(),
                'expires_at' => $expiresAt->format(DATE_ATOM),
                ...$context,
            ], JSON_THROW_ON_ERROR),
        );
    }

    /** @return array<string, string|null> */
    private function consume(ChallengeSessionKey $key, string $expectedPurpose): array
    {
        $raw = Redis::command('GETDEL', [$this->redisKey($key)]);
        if (! is_string($raw)) {
            throw new ChallengeSessionNotFoundException();
        }

        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data)
            || ! isset($data['purpose'], $data['challenge'], $data['options'], $data['expires_at'])) {
            throw new ChallengeSessionNotFoundException();
        }
        if ($data['purpose'] !== $expectedPurpose) {
            throw new ChallengeSessionPurposeMismatchException();
        }
        $expiresAt = new DateTimeImmutable((string) $data['expires_at']);
        if ($expiresAt <= new DateTimeImmutable()) {
            throw new ChallengeSessionNotFoundException();
        }

        /** @var array<string, string|null> $data */
        return $data;
    }

    /** @param array<string, string|null> $data */
    private function signupSession(array $data): SignupSession
    {
        $accountType = isset($data['account_type'])
            ? AccountType::tryFrom($data['account_type'])
            : null;
        $oneTimeToken = isset($data['one_time_token'])
            ? new OneTimeToken($data['one_time_token'])
            : null;

        return new SignupSession($accountType, $oneTimeToken, $data['return_to'] ?? null);
    }

    private function redisKey(ChallengeSessionKey $key): string
    {
        return self::KEY_PREFIX . $key;
    }
}
