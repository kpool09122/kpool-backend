<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\OAuthStateSession;
use Source\Identity\Application\Service\OAuthStateStorageServiceInterface;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Shared\Domain\Support\TypedValue;

class OAuthStateStorageService implements OAuthStateStorageServiceInterface
{
    private const string KEY_PREFIX = 'oauth_state:';

    public function __construct(private readonly Request $request)
    {
    }

    /**
     * @throws InvalidOAuthStateException
     */
    public function store(OAuthStateSession $session): void
    {
        $key = $this->buildKey($session->state);
        $ttl = $session->expiresAt->getTimestamp() - time();

        if ($ttl <= 0) {
            throw new InvalidOAuthStateException('OAuth state has already expired.');
        }

        Redis::setex($key, $ttl, json_encode([
            'expires_at' => $session->expiresAt->format(DATE_ATOM),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @throws InvalidOAuthStateException
     */
    public function consume(OAuthState $state): OAuthStateSession
    {
        $key = $this->buildKey($state);
        $raw = Redis::command('GETDEL', [$key]);

        if (! is_string($raw)) {
            throw new InvalidOAuthStateException();
        }

        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data) || ! isset($data['expires_at'])) {
            throw new InvalidOAuthStateException();
        }
        $expiresAt = new DateTimeImmutable(TypedValue::string($data['expires_at']));
        if ($expiresAt <= new DateTimeImmutable()) {
            throw new InvalidOAuthStateException();
        }

        return new OAuthStateSession($state, $expiresAt);
    }

    private function buildKey(OAuthState $state): string
    {
        if (! $this->request->hasSession()) {
            throw new InvalidOAuthStateException('An OAuth session is required.');
        }

        return self::KEY_PREFIX . $state . ':' . $this->request->session()->getId();
    }
}
