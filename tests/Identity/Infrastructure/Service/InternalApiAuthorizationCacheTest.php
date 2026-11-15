<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Illuminate\Support\Str;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Factory\AuthorizationConsentFactory;
use Source\Identity\Infrastructure\Service\InternalApiAuthorizationCache;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Tests\TestCase;

class InternalApiAuthorizationCacheTest extends TestCase
{
    public function testForgettingTheSourceTokenCausesTheNextRequestToReloadAuthorization(): void
    {
        $cache = $this->app()->make(InternalApiAuthorizationCache::class);
        $tokenId = (string) Str::uuid();
        $client = new OAuthClientIdentifier((string) Str::uuid());
        $account = new AccountIdentifier('0199f700-0000-7000-8000-000000000001');
        $consent = new AuthorizationConsentFactory(new UuidGenerator())->create(
            new IdentityIdentifier('0199f700-0000-7000-8000-000000000003'),
            $client,
            [AuthorizationScope::WikiRead],
            [new AuthorizationTarget($account)],
            $account,
            new AccountPrincipalIdentifier('0199f700-0000-7000-8000-000000000004'),
            $account,
            'https://mcp.example/mcp',
            now()->toDateTimeImmutable(),
        );
        $token = new ValidatedAuthorizationToken($tokenId, $consent, [AuthorizationScope::WikiRead], now()->addMinute()->toDateTimeImmutable());
        $loads = 0;
        $load = static function () use ($token, &$loads): ValidatedAuthorizationToken {
            ++$loads;

            return $token;
        };

        $cache->resolve($tokenId, $load);
        $cache->resolve($tokenId, $load);
        self::assertSame(1, $loads);

        $cache->forget($tokenId);
        $cache->resolve($tokenId, $load);

        self::assertSame(2, $loads);
    }
}
