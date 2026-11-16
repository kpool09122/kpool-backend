<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\AccountResolver;
use Application\Http\Context\AuthContextCache;
use Application\Http\Middleware\EnsureAccountActive;
use Application\Http\Middleware\EnsurePrincipalActive;
use Application\Http\Middleware\ResolveAccountContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Delegation\Application\Exception\DelegationUnavailableException;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInput;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInterface;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\CreateDelegatedApiAuthenticationContext;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class OAuthAccountSelectionTest extends TestCase
{
    public function testOAuthConnectionCanSwitchToAnAccountWhenDelegationBecomesAvailable(): void
    {
        [$context, $delegation] = CreateDelegatedApiAuthenticationContext::create();
        $original = (string) $context->originalAccountIdentifier();
        $delegation->update(['status' => 'pending', 'approved_at' => null]);
        request()->attributes->set(ApiAuthenticationContext::class, $context);

        $query = $this->app()->make(GetAuthenticatedIdentityInterface::class);
        $identity = new GetAuthenticatedIdentityInput($context->identityIdentifier());
        $snapshot = $query->process($identity)->toArray();
        self::assertSame($original, $snapshot['accountIdentifier']);
        self::assertIsArray($snapshot['switchableAccounts']);
        self::assertCount(1, $snapshot['switchableAccounts']);

        $delegation->update(['status' => 'approved', 'approved_at' => now()]);
        $snapshot = $query->process($identity)->toArray();
        self::assertIsArray($snapshot['switchableAccounts']);
        self::assertCount(2, $snapshot['switchableAccounts']);

        CreateDelegatedApiAuthenticationContext::switchTo($context, new DelegationIdentifier($delegation->id));
        self::assertSame($delegation->delegator_account_id, $query->process($identity)->toArray()['accountIdentifier']);

        $delegation->update(['status' => 'rejected', 'rejected_at' => now()]);
        $snapshot = $query->process($identity)->toArray();
        self::assertNull($snapshot['accountIdentifier']);
        self::assertIsArray($snapshot['switchableAccounts']);
        self::assertCount(1, $snapshot['switchableAccounts']);

        CreateDelegatedApiAuthenticationContext::switchTo($context, null);
        self::assertSame($original, $query->process($identity)->toArray()['accountIdentifier']);
    }

    public function testOAuthAccountSnapshotDoesNotExposeIdentityOrAuthenticationDetails(): void
    {
        [$context, $delegation] = CreateDelegatedApiAuthenticationContext::create();
        request()->attributes->set(ApiAuthenticationContext::class, $context);
        CreateDelegatedApiAuthenticationContext::switchTo($context, new DelegationIdentifier($delegation->id));
        $query = $this->app()->make(GetAuthenticatedIdentityInterface::class);
        $snapshot = $query->process(new GetAuthenticatedIdentityInput($context->identityIdentifier()))->toArray();
        self::assertSame($delegation->delegator_account_id, $snapshot['accountIdentifier']);
        self::assertArrayNotHasKey('authenticationMethods', $snapshot);
        self::assertArrayNotHasKey('email', $snapshot);
        self::assertArrayNotHasKey('profileImage', $snapshot);
        self::assertArrayNotHasKey('siteManagementPolicies', $snapshot);
        self::assertNotNull($snapshot['originalAccount']);
        self::assertIsArray($snapshot['switchableAccounts']);
        self::assertCount(2, $snapshot['switchableAccounts']);
    }

    public function testWarmAccountCacheCannotAuthorizeRevokedDelegation(): void
    {
        [$context, $delegation] = CreateDelegatedApiAuthenticationContext::create();
        request()->attributes->set(ApiAuthenticationContext::class, $context);
        $resolver = $this->app()->make(AccountResolver::class);
        $cache = $this->app()->make(AuthContextCache::class);
        CreateDelegatedApiAuthenticationContext::switchTo($context, new DelegationIdentifier($delegation->id));
        $cache->resolveAccount($context->identityIdentifier(), fn () => $resolver->resolve($context->identityIdentifier()));
        $delegation->update(['status' => 'rejected', 'rejected_at' => now()]);
        $this->expectException(DelegationUnavailableException::class);
        $cache->resolveAccount($context->identityIdentifier(), fn () => $resolver->resolve($context->identityIdentifier()));
    }

    public function testOAuthSwitchRemainsReachableAfterSelectedDelegationIsRevoked(): void
    {
        [$context, $delegation] = CreateDelegatedApiAuthenticationContext::create();
        request()->attributes->set(ApiAuthenticationContext::class, $context);
        CreateDelegatedApiAuthenticationContext::switchTo($context, new DelegationIdentifier($delegation->id));
        $delegation->update(['status' => 'rejected', 'rejected_at' => now()]);
        $request = Request::create('/api/v1/account/accounts/switch', 'POST', ['delegationIdentifier' => StrTestHelper::generateUuid()]);
        $request->attributes->set(ApiAuthenticationContext::class, $context);
        $this->app()->instance('request', $request);
        Auth::onceUsingId((string) $context->identityIdentifier());
        foreach ([EnsureAccountActive::class, EnsurePrincipalActive::class, ResolveAccountContext::class] as $middleware) {
            $response = $this->app()->make($middleware)->handle($request, static fn () => response('switchable'));
            self::assertSame('switchable', $response->getContent());
        }
    }
}
