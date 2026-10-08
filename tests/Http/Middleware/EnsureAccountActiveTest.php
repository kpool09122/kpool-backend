<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\AuthContextCache;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Middleware\EnsureAccountActive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class EnsureAccountActiveTest extends TestCase
{
    #[DataProvider('accountStatusProvider')]
    public function testChecksOriginalAndEffectiveAccountStatus(
        AccountStatus $originalStatus,
        AccountStatus $effectiveStatus,
        bool $returnToOriginal,
        ?string $expectedError,
    ): void {
        Auth::shouldReceive('id')->once()->andReturn((string) $this->identityIdentifier());
        /** @var AuthContextCache&Mockery\MockInterface $cache */
        $cache = Mockery::mock(AuthContextCache::class);
        $cache->shouldReceive('resolveAccount')->once()->andReturn($this->context($effectiveStatus, $originalStatus));
        $middleware = new EnsureAccountActive($cache, $this->accountResolver());
        $request = $returnToOriginal
            ? Request::create('/api/v1/account/accounts/switch', 'POST', ['delegationIdentifier' => null])
            : Request::create('/api/v1/wiki/images', 'GET');
        $nextCalled = false;
        $shouldProceed = $expectedError === null;

        try {
            $response = $middleware->handle($request, function () use (&$nextCalled) {
                $nextCalled = true;

                return response('ok');
            });
            $this->assertNull($expectedError);
            $this->assertSame('ok', $response->getContent());
        } catch (ForbiddenHttpException $exception) {
            $this->assertNotNull($expectedError);
            $this->assertSame(403, $exception->getHttpStatus());
            $this->assertSame($expectedError, $exception->toProblemDetails()['code']);
        }

        $this->assertSame($shouldProceed, $nextCalled);
    }

    /** @return array<string, array{AccountStatus, AccountStatus, bool, string|null}> */
    public static function accountStatusProvider(): array
    {
        $cases = [];
        foreach (AccountStatus::cases() as $originalStatus) {
            foreach (AccountStatus::cases() as $effectiveStatus) {
                foreach ([false, true] as $returnToOriginal) {
                    $expectedError = match ($originalStatus) {
                        AccountStatus::PENDING => 'account_setup_required',
                        AccountStatus::SUSPENDED => 'account_suspended',
                        AccountStatus::ACTIVE => $returnToOriginal || $effectiveStatus === AccountStatus::ACTIVE ? null : 'account_suspended',
                    };
                    $cases[$originalStatus->value . '/' . $effectiveStatus->value . '/' . ($returnToOriginal ? 'return' : 'operation')] = [
                        $originalStatus, $effectiveStatus, $returnToOriginal, $expectedError,
                    ];
                }
            }
        }

        return $cases;
    }

    public function testRejectsSwitchToDelegationWhileEffectiveAccountIsInactive(): void
    {
        Auth::shouldReceive('id')->once()->andReturn((string) $this->identityIdentifier());
        /** @var AuthContextCache&Mockery\MockInterface $cache */
        $cache = Mockery::mock(AuthContextCache::class);
        $cache->shouldReceive('resolveAccount')->once()->andReturn($this->context(AccountStatus::SUSPENDED, AccountStatus::ACTIVE));
        $middleware = new EnsureAccountActive($cache, $this->accountResolver());

        $this->expectException(ForbiddenHttpException::class);
        $middleware->handle(
            Request::create('/api/v1/account/accounts/switch', 'POST', ['delegationIdentifier' => StrTestHelper::generateUuid()]),
            function () {
                $this->fail('The request must not reach the action.');
            },
        );
    }

    public function testAllowsActiveOriginalAccount(): void
    {
        Auth::shouldReceive('id')->once()->andReturn((string) $this->identityIdentifier());
        /** @var AuthContextCache&Mockery\MockInterface $cache */
        $cache = Mockery::mock(AuthContextCache::class);
        $cache->shouldReceive('resolveAccount')->once()->andReturn($this->context(AccountStatus::ACTIVE));
        $middleware = new EnsureAccountActive($cache, $this->accountResolver());

        $response = $middleware->handle(
            Request::create('/api/v1/wiki/wiki/create', 'POST'),
            fn () => response('ok'),
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function testRejectsPendingOriginalAccountEvenWhenEffectiveAccountIsActive(): void
    {
        Auth::shouldReceive('id')->once()->andReturn((string) $this->identityIdentifier());
        /** @var AuthContextCache&Mockery\MockInterface $cache */
        $cache = Mockery::mock(AuthContextCache::class);
        $cache->shouldReceive('resolveAccount')->once()->andReturn(
            $this->context(AccountStatus::ACTIVE, AccountStatus::PENDING),
        );
        $middleware = new EnsureAccountActive($cache, $this->accountResolver());

        try {
            $middleware->handle(Request::create('/api/v1/account/accounts/switch', 'POST'), fn () => response('unexpected'));
            $this->fail('ForbiddenHttpException was not thrown.');
        } catch (ForbiddenHttpException $exception) {
            $this->assertSame('account_setup_required', $exception->toProblemDetails()['code']);
        }
    }

    public function testRejectsSuspendedAccountWithDistinctCode(): void
    {
        Auth::shouldReceive('id')->once()->andReturn((string) $this->identityIdentifier());
        /** @var AuthContextCache&Mockery\MockInterface $cache */
        $cache = Mockery::mock(AuthContextCache::class);
        $cache->shouldReceive('resolveAccount')->once()->andReturn($this->context(AccountStatus::SUSPENDED));
        $middleware = new EnsureAccountActive($cache, $this->accountResolver());

        try {
            $middleware->handle(Request::create('/api/v1/wiki/images', 'GET'), fn () => response('unexpected'));
            $this->fail('ForbiddenHttpException was not thrown.');
        } catch (ForbiddenHttpException $exception) {
            $this->assertSame('account_suspended', $exception->toProblemDetails()['code']);
        }
    }

    #[DataProvider('exemptPathProvider')]
    public function testAllowsExplicitSetupExceptionsWithoutResolvingAccount(string $path): void
    {
        /** @var AuthContextCache&Mockery\MockInterface $cache */
        $cache = Mockery::mock(AuthContextCache::class);
        $cache->shouldNotReceive('resolveAccount');
        $middleware = new EnsureAccountActive($cache, $this->accountResolver());

        $response = $middleware->handle(Request::create($path, 'GET'), fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    /** @return array<string, array{string}> */
    public static function exemptPathProvider(): array
    {
        return [
            'me' => ['/api/v1/identity/auth/me'],
            'setup' => ['/api/v1/account/accounts/setup'],
            'logout' => ['/api/v1/identity/auth/logout'],
        ];
    }

    private function context(
        AccountStatus $status,
        ?AccountStatus $originalStatus = null,
    ): AccountContext {
        $identityIdentifier = $this->identityIdentifier();

        return new AccountContext(
            principal: new Principal(
                new PrincipalIdentifier(StrTestHelper::generateUuid()),
                $identityIdentifier,
                new AccountIdentifier(StrTestHelper::generateUuid()),
            ),
            accountType: AccountType::CORPORATION,
            accountStatus: $status,
            accountCategory: AccountCategory::GENERAL,
            originalAccountStatus: $originalStatus,
        );
    }

    private function identityIdentifier(): IdentityIdentifier
    {
        return new IdentityIdentifier(StrTestHelper::generateUuid());
    }

    private function accountResolver(): AccountResolver
    {
        return $this->app()->make(AccountResolver::class);
    }
}
