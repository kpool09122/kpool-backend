<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Illuminate\Support\Facades\Cache;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Throwable;

readonly class InternalApiAuthorizationCache
{
    private const int TTL_SECONDS = 60;
    private const string KEY_PREFIX = 'internal-api-authorization:context:';

    /** @param callable(): ValidatedAuthorizationToken $loadFromDatabase */
    public function resolve(string $tokenIdentifier, callable $loadFromDatabase): ValidatedAuthorizationToken
    {
        $key = self::KEY_PREFIX . $tokenIdentifier;

        try {
            $cached = Cache::store('redis')->get($key);
        } catch (Throwable) {
            return $loadFromDatabase();
        }

        if ($cached instanceof ValidatedAuthorizationToken) {
            return $cached;
        }

        $authorizationToken = $loadFromDatabase();
        $ttl = min(self::TTL_SECONDS, $authorizationToken->expiresAt->getTimestamp() - now()->getTimestamp());
        if ($ttl > 0) {
            try {
                Cache::store('redis')->put($key, $authorizationToken, $ttl);
            } catch (Throwable) {
                // DB verification remains available when Redis writes fail.
            }
        }

        return $authorizationToken;
    }

    public function forget(string $tokenIdentifier): void
    {
        Cache::store('redis')->forget(self::KEY_PREFIX . $tokenIdentifier);
    }
}
