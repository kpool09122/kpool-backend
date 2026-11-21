<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\AuthContextCache;
use Application\Http\Context\MonetizationContext;
use Illuminate\Support\Facades\Redis;
use Psr\Log\NullLogger;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier as MonetizationPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MonetizationContextCacheTest extends TestCase
{
    public function testResolveMonetizationSerializesAndDeserializes(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new MonetizationPrincipalIdentifier(StrTestHelper::generateUuid());

        Redis::shouldReceive('get')
            ->once()
            ->with('auth-context:monetization:' . $identityIdentifier . ':' . $accountIdentifier)
            ->andReturn(json_encode([
                'principalIdentifier' => (string) $principalIdentifier,
            ]));
        Redis::shouldReceive('setex')->never();

        $context = (new AuthContextCache(new NullLogger()))->resolveMonetization(
            $identityIdentifier,
            $accountIdentifier,
            fn () => new MonetizationContext(new MonetizationPrincipalIdentifier(StrTestHelper::generateUuid())),
        );

        $this->assertSame((string) $principalIdentifier, (string) $context->principalIdentifier);
    }

    public function testResolveMonetizationStoresByIdentityAndAccount(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new MonetizationPrincipalIdentifier(StrTestHelper::generateUuid());

        Redis::shouldReceive('get')
            ->once()
            ->with('auth-context:monetization:' . $identityIdentifier . ':' . $accountIdentifier)
            ->andReturn(null);
        Redis::shouldReceive('setex')
            ->once()
            ->withArgs(
                function (string $key, int $ttl, string $payload) use ($identityIdentifier, $accountIdentifier, $principalIdentifier): bool {
                    $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
                    self::assertIsArray($decoded);

                    return $key === 'auth-context:monetization:' . $identityIdentifier . ':' . $accountIdentifier
                && $ttl === 3600 && $decoded['principalIdentifier'] === (string) $principalIdentifier;
                }
            );

        $context = (new AuthContextCache(new NullLogger()))->resolveMonetization(
            $identityIdentifier,
            $accountIdentifier,
            fn () => new MonetizationContext($principalIdentifier),
        );

        $this->assertSame((string) $principalIdentifier, (string) $context->principalIdentifier);
    }
}
