<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Context\AuthContextCache;
use Application\Http\Context\WikiContext;
use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Source\Account\Principal\Domain\Entity\Principal as AccountPrincipal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier as AccountPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier as WikiPrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AuthContextCacheTest extends TestCase
{
    public function testResolveActorReturnsCachedContextWithoutCallingDbResolver(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationIdentifier = StrTestHelper::generateUuid();
        $originalIdentityIdentifier = StrTestHelper::generateUuid();

        Redis::shouldReceive('get')
            ->once()
            ->with('auth-context:actor:' . $identityIdentifier)
            ->andReturn(json_encode([
                'identityIdentifier' => (string) $identityIdentifier,
                'language' => 'ja',
                'delegationIdentifier' => $delegationIdentifier,
                'originalIdentityIdentifier' => $originalIdentityIdentifier,
            ]));
        Redis::shouldReceive('setex')->never();

        $context = (new AuthContextCache())->resolveActor(
            $identityIdentifier,
            fn () => throw new RuntimeException('DB resolver must not be called'),
        );

        $this->assertSame((string) $identityIdentifier, (string) $context->identityIdentifier);
        $this->assertSame(Language::JAPANESE, $context->language);
        $this->assertSame($delegationIdentifier, (string) $context->delegationIdentifier);
        $this->assertSame($originalIdentityIdentifier, (string) $context->originalIdentityIdentifier);
    }

    public function testResolveActorFallsBackToDbAndStoresOnMiss(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')
            ->once()
            ->withArgs(
                fn (string $key, int $ttl, string $payload) =>
                $key === 'auth-context:actor:' . $identityIdentifier
                && $ttl === 3600
                && json_decode($payload, true)['identityIdentifier'] === (string) $identityIdentifier
            );

        $context = (new AuthContextCache())->resolveActor(
            $identityIdentifier,
            fn () => new ActorContext($identityIdentifier, Language::ENGLISH, null, null),
        );

        $this->assertSame(Language::ENGLISH, $context->language);
    }

    public function testResolveActorFallsBackToDbWhenRedisReadThrows(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        Redis::shouldReceive('get')->once()->andThrow(new RuntimeException('redis down'));
        Redis::shouldReceive('setex')->once();

        $context = (new AuthContextCache())->resolveActor(
            $identityIdentifier,
            fn () => new ActorContext($identityIdentifier, Language::ENGLISH, null, null),
        );

        $this->assertSame((string) $identityIdentifier, (string) $context->identityIdentifier);
    }

    public function testResolveActorContinuesWhenRedisWriteThrows(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->once()->andThrow(new RuntimeException('redis down'));

        $context = (new AuthContextCache())->resolveActor(
            $identityIdentifier,
            fn () => new ActorContext($identityIdentifier, Language::ENGLISH, null, null),
        );

        $this->assertSame(Language::ENGLISH, $context->language);
    }

    public function testResolveAccountSerializesAndDeserializes(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new AccountPrincipalIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        Redis::shouldReceive('get')->once()->andReturn(json_encode([
            'principalIdentifier' => (string) $principalIdentifier,
            'identityIdentifier' => (string) $identityIdentifier,
            'accountIdentifier' => (string) $accountIdentifier,
            'accountType' => AccountType::CORPORATION->value,
            'accountPolicies' => [
                [
                    'policyIdentifier' => '019de7f3-78f3-7b55-9ed5-17f63e14d5aa',
                    'name' => 'ACCOUNT_OWNER_BASIC',
                    'isSystemPolicy' => true,
                    'statements' => [],
                ],
            ],
        ]));
        Redis::shouldReceive('setex')->never();

        $context = (new AuthContextCache())->resolveAccount(
            $identityIdentifier,
            fn () => throw new RuntimeException('DB resolver must not be called'),
        );

        $this->assertSame((string) $principalIdentifier, (string) $context->principal()->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $context->principal()->identityIdentifier());
        $this->assertSame((string) $accountIdentifier, (string) $context->principal()->accountIdentifier());
        $this->assertSame(AccountType::CORPORATION, $context->accountType());
        $this->assertSame('ACCOUNT_OWNER_BASIC', $context->accountPolicies()[0]['name']);
    }

    public function testResolveAccountFallsBackToDbAndStoresPrincipalContextOnOldPayload(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new AccountPrincipalIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        Redis::shouldReceive('get')->once()->andReturn(json_encode([
            'accountIdentifier' => (string) $accountIdentifier,
            'role' => 'admin',
        ]));
        Redis::shouldReceive('setex')
            ->once()
            ->withArgs(function (
                string $key,
                int $ttl,
                string $payload,
            ) use ($identityIdentifier, $principalIdentifier, $accountIdentifier): bool {
                $decoded = json_decode($payload, true);

                return $key === 'auth-context:account:' . $identityIdentifier
                    && $ttl === 3600
                    && $decoded['principalIdentifier'] === (string) $principalIdentifier
                    && $decoded['identityIdentifier'] === (string) $identityIdentifier
                    && $decoded['accountIdentifier'] === (string) $accountIdentifier
                    && $decoded['accountType'] === AccountType::CORPORATION->value
                    && ! array_key_exists('accountRole', $decoded)
                    && $decoded['accountPolicies'][0]['name'] === 'ACCOUNT_ADMIN_BASIC';
            });

        $context = (new AuthContextCache())->resolveAccount(
            $identityIdentifier,
            fn () => new AccountContext(
                new AccountPrincipal($principalIdentifier, $identityIdentifier, $accountIdentifier),
                AccountType::CORPORATION,
                [
                    [
                        'policyIdentifier' => '019de7f3-78f3-7b55-9ed5-17f63e14d5bb',
                        'name' => 'ACCOUNT_ADMIN_BASIC',
                        'isSystemPolicy' => true,
                        'statements' => [],
                    ],
                ],
            ),
        );

        $this->assertSame((string) $principalIdentifier, (string) $context->principal()->principalIdentifier());
    }

    public function testResolveWikiSerializesAndDeserializes(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new WikiPrincipalIdentifier(StrTestHelper::generateUuid());

        Redis::shouldReceive('get')->once()->andReturn(json_encode([
            'principalIdentifier' => (string) $principalIdentifier,
        ]));
        Redis::shouldReceive('setex')->never();

        $context = (new AuthContextCache())->resolveWiki(
            $identityIdentifier,
            fn () => new WikiContext(new WikiPrincipalIdentifier(StrTestHelper::generateUuid())),
        );

        $this->assertSame((string) $principalIdentifier, (string) $context->principalIdentifier);
    }

    public function testInvalidPayloadFallsBackToDb(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        Redis::shouldReceive('get')->once()->andReturn(json_encode(['language' => 'ja']));
        Redis::shouldReceive('setex')->once();

        $context = (new AuthContextCache())->resolveActor(
            $identityIdentifier,
            fn () => new ActorContext($identityIdentifier, Language::ENGLISH, null, null),
        );

        $this->assertSame(Language::ENGLISH, $context->language);
    }
}
