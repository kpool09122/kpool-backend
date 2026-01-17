<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Illuminate\Support\Facades\Redis;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\ValueObject\OAuthState;

class OAuthStateRepository implements OAuthStateRepositoryInterface
{
    private const string KEY_PREFIX = 'oauth_state:';

    /**
     * @throws InvalidOAuthStateException
     */
    public function store(OAuthState $state): void
    {
        $key = $this->buildKey($state);
        $ttl = $state->expiresAt()->getTimestamp() - time();

        if ($ttl <= 0) {
            throw new InvalidOAuthStateException('OAuth state has already expired.');
        }

        Redis::setex($key, $ttl, '1');
    }

    /**
     * @throws InvalidOAuthStateException
     */
    public function consume(OAuthState $state): void
    {
        $key = $this->buildKey($state);
        $exists = Redis::exists($key);

        if ($exists === 0) {
            throw new InvalidOAuthStateException();
        }

        Redis::del($key);
    }

    private function buildKey(OAuthState $state): string
    {
        return self::KEY_PREFIX . $state;
    }
}
