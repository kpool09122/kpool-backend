<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSession;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSessionStorageServiceInterface;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PasskeyRecoveryOAuthSessionStorageService implements PasskeyRecoveryOAuthSessionStorageServiceInterface
{
    private const string KEY_PREFIX = 'passkey_recovery_oauth_session:';

    public function store(OAuthState $state, PasskeyRecoveryOAuthSession $session): void
    {
        $ttl = $session->expiresAt->getTimestamp() - time();
        if ($ttl <= 0) {
            throw new InvalidOAuthStateException('Passkey recovery OAuth session has already expired.');
        }

        Redis::setex($this->key($state), $ttl, json_encode([
            'identity_id' => (string) $session->identityIdentifier,
            'provider' => $session->provider->value,
            'expires_at' => $session->expiresAt->format(DATE_ATOM),
        ], JSON_THROW_ON_ERROR));
    }

    public function consume(OAuthState $state): ?PasskeyRecoveryOAuthSession
    {
        $raw = Redis::command('GETDEL', [$this->key($state)]);
        if (! is_string($raw)) {
            return null;
        }

        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data) || ! isset($data['identity_id'], $data['provider'], $data['expires_at'])) {
            return null;
        }

        $expiresAt = new DateTimeImmutable((string) $data['expires_at']);
        if ($expiresAt <= new DateTimeImmutable()) {
            return null;
        }

        return new PasskeyRecoveryOAuthSession(
            new IdentityIdentifier((string) $data['identity_id']),
            SocialProvider::from((string) $data['provider']),
            $expiresAt,
        );
    }

    private function key(OAuthState $state): string
    {
        return self::KEY_PREFIX . $state;
    }
}
