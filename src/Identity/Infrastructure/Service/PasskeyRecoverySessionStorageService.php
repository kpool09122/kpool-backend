<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySession;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySessionStorageServiceInterface;
use Source\Identity\Domain\Exception\PasskeyRecoverySessionInvalidException;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class PasskeyRecoverySessionStorageService implements PasskeyRecoverySessionStorageServiceInterface
{
    private const int TTL_SECONDS = 600;
    private const string KEY_PREFIX = 'passkey_recovery_session:';

    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function issue(IdentityIdentifier $identityIdentifier, string $method): PasskeyRecoveryKey
    {
        $key = new PasskeyRecoveryKey($this->uuidGenerator->generate());
        $expiresAt = new DateTimeImmutable('+' . self::TTL_SECONDS . ' seconds');
        Redis::setex($this->key($key), self::TTL_SECONDS, json_encode([
            'identity_id' => (string) $identityIdentifier,
            'scope' => 'passkey.recover',
            'method' => $method,
            'expires_at' => $expiresAt->format(DATE_ATOM),
        ], JSON_THROW_ON_ERROR));

        return $key;
    }

    public function requireValid(PasskeyRecoveryKey $key): PasskeyRecoverySession
    {
        $raw = Redis::get($this->key($key));
        if (! is_string($raw)) {
            throw new PasskeyRecoverySessionInvalidException('Passkey recovery session is missing or expired.');
        }
        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data)
            || ($data['scope'] ?? null) !== 'passkey.recover'
            || ! isset($data['identity_id'], $data['method'], $data['expires_at'])) {
            throw new PasskeyRecoverySessionInvalidException();
        }
        $expiresAt = new DateTimeImmutable((string) $data['expires_at']);
        if ($expiresAt <= new DateTimeImmutable()) {
            throw new PasskeyRecoverySessionInvalidException('Passkey recovery session has expired.');
        }

        return new PasskeyRecoverySession(
            new IdentityIdentifier((string) $data['identity_id']),
            (string) $data['method'],
            $expiresAt,
        );
    }

    public function consume(PasskeyRecoveryKey $key, IdentityIdentifier $identityIdentifier): void
    {
        $session = $this->requireValid($key);
        if ((string) $session->identityIdentifier !== (string) $identityIdentifier) {
            throw new PasskeyRecoverySessionInvalidException('Passkey recovery session belongs to another identity.');
        }
        $raw = Redis::command('GETDEL', [$this->key($key)]);
        if (! is_string($raw)) {
            throw new PasskeyRecoverySessionInvalidException('Passkey recovery session has already been consumed.');
        }
    }

    private function key(PasskeyRecoveryKey $key): string
    {
        return self::KEY_PREFIX . $key;
    }
}
