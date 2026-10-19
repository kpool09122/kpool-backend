<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\ActorContext;
use Application\Http\Context\AuthContextCache;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Middleware\EnsurePrincipalActive;
use Application\Http\Middleware\ResolveAccountContext;
use ArrayObject;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;
use RuntimeException;
use Source\Account\Account\Application\Service\CurrentAccount;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountInput;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountInterface;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountOutputPort;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class EnsurePrincipalActiveTest extends TestCase
{
    /** @return iterable<string, array{string, string, bool}> */
    public static function statuses(): iterable
    {
        foreach (['active', 'suspended', 'forced_stop', 'unknown'] as $account) {
            foreach (['active', 'suspended', 'forced_stop', 'unknown', 'missing'] as $child) {
                foreach (['wiki', 'site-management'] as $context) {
                    yield "$context/$account/$child" => [$account, "$context/$child", $account === 'active' && in_array($child, ['active', 'missing'], true)];
                }
            }
        }
    }

    #[DataProvider('statuses')]
    public function testFreshStatusesGateProtectedRequests(string $status, string $childCase, bool $allowed): void
    {
        [$context, $child] = explode('/', $childCase);
        [$identity, $account, $principal] = $this->fixture();
        DB::table('account_principals')->where('id', (string) $principal)->update(['status' => $status]);
        if ($child !== 'missing') {
            DB::table($context === 'wiki' ? 'wiki_principals' : 'site_management_principals')->insert([
                'id' => StrTestHelper::generateUuid(), 'identity_id' => (string) $identity,
                'account_id' => (string) $account, 'status' => $child,
            ]);
        }
        Auth::shouldReceive('id')->andReturn((string) $identity);
        $this->assertAccess(Request::create("/api/v1/$context/principal-groups", 'GET'), $allowed);
    }

    public function testSetupBeforePrincipalExistsDoesNotDeny(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        Auth::shouldReceive('id')->andReturn((string) $identity);
        $this->assertAccess(Request::create('/api/v1/account/accounts/setup', 'PATCH'), true);
    }

    /** @return iterable<string, array{string, string}> */
    public static function selfPaths(): iterable
    {
        yield 'me' => ['/api/v1/identity/auth/me', 'GET'];
        yield 'wiki me' => ['/api/v1/wiki/principal/me', 'GET'];
        yield 'logout' => ['/api/v1/identity/auth/logout', 'POST'];
    }

    #[DataProvider('selfPaths')]
    public function testStoppedSelfStateAndLogoutRemainAvailable(string $path, string $method): void
    {
        [$identity, , $principal] = $this->fixture();
        DB::table('account_principals')->where('id', (string) $principal)->update(['status' => 'forced_stop']);
        Auth::shouldReceive('id')->andReturn((string) $identity);
        $this->assertAccess(Request::create($path, $method), true);
    }

    public function testSetupCannotBypassStoppedExistingPrincipal(): void
    {
        [$identity, , $principal] = $this->fixture();
        DB::table('account_principals')->where('id', (string) $principal)->update(['status' => 'suspended']);
        Auth::shouldReceive('id')->andReturn((string) $identity);
        $this->assertAccess(Request::create('/api/v1/account/accounts/setup', 'PATCH'), false);
    }

    /** @return iterable<string, array{string, string, string, bool}> */
    public static function delegationCases(): iterable
    {
        foreach (['active', 'suspended', 'forced_stop'] as $original) {
            foreach (['active', 'suspended', 'forced_stop'] as $effective) {
                foreach (['protected', 'return', 'switch'] as $operation) {
                    yield "$original/$effective/$operation" => [$original, $effective, $operation, $original === 'active' && ($operation === 'return' || $effective === 'active')];
                }
            }
        }
    }

    #[DataProvider('delegationCases')]
    public function testDelegationCannotBypassEitherPrincipalButAllowsReturnFromStoppedTarget(string $originalStatus, string $effectiveStatus, string $operation, bool $allowed): void
    {
        [$identity, $account, $principal] = $this->fixture();
        $target = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $target);
        $effective = new PrincipalIdentifier(StrTestHelper::generateUuid());
        DB::table('account_principals')->insert(['id' => (string) $effective, 'identity_id' => (string) $identity, 'account_id' => (string) $target, 'status' => $effectiveStatus]);
        DB::table('account_principals')->where('id', (string) $principal)->update(['status' => $originalStatus]);
        $delegation = new Delegation(new DelegationIdentifier(StrTestHelper::generateUuid()), new AffiliationIdentifier(StrTestHelper::generateUuid()), $account, $target, $account, DelegationStatus::APPROVED, DelegationDirection::FROM_AGENCY, new DateTimeImmutable(), new DateTimeImmutable(), null);
        $repository = $this->createStub(DelegationRepositoryInterface::class);
        $repository->method('findById')->willReturn($delegation);
        $this->app()->instance(DelegationRepositoryInterface::class, $repository);
        $this->app()->make(CurrentAccountServiceInterface::class)->save(new CurrentAccount($identity, $account, $principal, $target, $effective, $delegation->delegationIdentifier()));
        Auth::shouldReceive('id')->andReturn((string) $identity);
        $request = $operation === 'protected' ? Request::create('/api/v1/account/principal-groups', 'GET')
            : Request::create('/api/v1/account/accounts/switch', 'POST', ['delegationIdentifier' => $operation === 'return' ? null : (string) $delegation->delegationIdentifier()]);
        $this->assertAccess($request, $allowed);
    }

    public function testWarmAuthorizationCacheCannotAuthorizeNewlyStoppedPrincipal(): void
    {
        [$identity, , $principal] = $this->fixture();
        $cache = $this->app()->make(AuthContextCache::class);
        $cache->resolveAccount($identity, fn () => $this->app()->make(AccountResolver::class)->resolve($identity));
        DB::table('account_principals')->where('id', (string) $principal)->update(['status' => 'suspended']);
        Auth::shouldReceive('id')->andReturn((string) $identity);
        $this->assertAccess(Request::create('/api/v1/account/principal-groups', 'GET'), false);
    }

    /** @return iterable<string, array{string}> */
    public static function protectedContextPaths(): iterable
    {
        yield 'Account' => ['/api/v1/account/principal-groups'];
        yield 'Wiki' => ['/api/v1/wiki/principal-groups'];
        yield 'SiteManagement' => ['/api/v1/site-management/principal-groups'];
    }

    #[DataProvider('protectedContextPaths')]
    public function testVerifiedSubjectIsUsedAfterReturningWhenCacheInvalidationFails(string $path): void
    {
        [$identity, $account, $principal] = $this->fixture();
        $target = new AccountIdentifier(StrTestHelper::generateUuid());
        $effective = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $target);
        DB::table('account_principals')->insert(['id' => (string) $effective, 'identity_id' => (string) $identity, 'account_id' => (string) $target, 'status' => 'active']);
        $delegation = new Delegation(new DelegationIdentifier(StrTestHelper::generateUuid()), new AffiliationIdentifier(StrTestHelper::generateUuid()), $account, $target, $account, DelegationStatus::APPROVED, DelegationDirection::FROM_AGENCY, new DateTimeImmutable(), new DateTimeImmutable(), null);
        $repository = $this->createStub(DelegationRepositoryInterface::class);
        $repository->method('findById')->willReturn($delegation);
        $this->app()->instance(DelegationRepositoryInterface::class, $repository);
        $selected = new CurrentAccount($identity, $account, $principal, $target, $effective, $delegation->delegationIdentifier());
        $currentAccountService = $this->createStub(CurrentAccountServiceInterface::class);
        $currentAccountService->method('find')->willReturnCallback(static function () use (&$selected): CurrentAccount {
            return $selected;
        });
        $currentAccountService->method('save')->willReturnCallback(static function (CurrentAccount $next) use (&$selected): void {
            $selected = $next;
        });
        $this->app()->instance(CurrentAccountServiceInterface::class, $currentAccountService);
        /** @var ArrayObject<string, string> $payloads */
        $payloads = new ArrayObject();
        Redis::shouldReceive('get')->andReturnUsing(static fn (string $key): ?string => $payloads[$key] ?? null);
        Redis::shouldReceive('setex')->once()->andReturnUsing(static function (string $key, int $ttl, string $payload) use ($payloads): void {
            $payloads[$key] = $payload;
        });
        Redis::shouldReceive('del')->once()->andThrow(new RuntimeException('DEL unavailable'));
        $cache = new AuthContextCache(new NullLogger());
        $this->app()->instance(AuthContextCache::class, $cache);
        $resolver = $this->app()->make(AccountResolver::class);
        $cached = $cache->resolveAccount($identity, fn () => $resolver->resolve($identity));
        self::assertSame((string) $target, (string) $cached->principal()->accountIdentifier());
        DB::table('account_principals')->where('id', (string) $effective)->update(['status' => 'suspended']);
        $output = $this->createMock(SwitchAccountOutputPort::class);
        $output->expects(self::once())->method('setCurrentAccount');
        $this->app()->make(SwitchAccountInterface::class)->process(new SwitchAccountInput($identity, $principal, null), $output);
        $cache->forgetCurrentAccount($identity);
        $stale = $cache->resolveAccount($identity, fn () => throw new RuntimeException('The warmed cache should still be present'));
        self::assertSame((string) $target, (string) $stale->principal()->accountIdentifier());
        self::assertSame('active', $stale->principal()->status()->value);
        Auth::shouldReceive('id')->andReturn((string) $identity);
        $this->app()->instance(ActorContext::class, new ActorContext($identity, Language::ENGLISH));
        $request = Request::create($path, 'GET');
        $response = (new EnsurePrincipalActive($resolver))->handle($request, function (Request $request) use ($resolver, $cache, $account, $principal) {
            return (new ResolveAccountContext($resolver, $cache))->handle($request, function () use ($account, $principal) {
                $used = app(AccountContext::class);
                self::assertSame((string) $account, (string) $used->principal()->accountIdentifier());
                self::assertSame((string) $principal, (string) $used->principal()->principalIdentifier());

                return response('ok');
            });
        });
        self::assertSame('ok', $response->getContent());
    }

    /** @return array{IdentityIdentifier, AccountIdentifier, PrincipalIdentifier} */
    private function fixture(): array
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        CreateAccount::create((string) $account);
        DB::table('account_principals')->insert(['id' => (string) $principal, 'identity_id' => (string) $identity, 'account_id' => (string) $account]);
        $this->app()->make(CurrentAccountServiceInterface::class)->save(new CurrentAccount($identity, $account, $principal, $account, $principal, null));

        return [$identity, $account, $principal];
    }

    private function assertAccess(Request $request, bool $allowed): void
    {
        $called = false;

        try {
            (new EnsurePrincipalActive($this->app()->make(AccountResolver::class)))->handle($request, function () use (&$called) {
                $called = true;

                return response('ok');
            });
        } catch (ForbiddenHttpException $exception) {
            self::assertSame('principal_inactive', $exception->toProblemDetails()['code']);
        }
        self::assertSame($allowed, $called);
    }
}
