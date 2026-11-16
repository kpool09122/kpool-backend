<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\AuthContextCache;
use Application\Http\Exceptions\TooManyRequestsHttpException;
use Application\Http\Middleware\EnforceApiRateLimit;
use Application\Http\RateLimit\ApiRateLimiter;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use RedisException;
use RuntimeException;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccountContext;
use Tests\Helper\CreateApiAuthenticationContext;
use Tests\TestCase;

class EnforceApiRateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('api_rate_limit', [
            'decay_seconds' => 60,
            'global' => ['query' => 10, 'command' => 10],
            'surfaces' => [
                'screen' => [
                    'account' => ['query' => 10, 'command' => 10],
                    'ip' => ['query' => 1, 'command' => 1],
                ],
            ],
        ]);
        Auth::shouldReceive('check')->andReturn(false);
    }

    public function testVerifiedBearerUsesTheSelectedAccountLimitWithoutBrowserSessionValidation(): void
    {
        $context = CreateApiAuthenticationContext::create();
        $request = Request::create('/api/v1/wiki/wikis/ja', 'GET');
        $request->attributes->set(ApiAuthenticationContext::class, $context);
        app()->instance(AccountContext::class, CreateAccountContext::create($context->identityIdentifier(), $context->initialAccountIdentifier()));
        $limiter = new RateLimiter(new Repository(new ArrayStore()));
        config()->set('api_rate_limit.surfaces.screen.account.query', 1);
        /** @var AuthServiceInterface&MockInterface $authService */
        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldNotReceive('isCurrentSessionValid');
        $authService->shouldNotReceive('logout');
        $middleware = new EnforceApiRateLimit(new ApiRateLimiter($limiter), $authService, app(AccountResolver::class), app(AuthContextCache::class));
        self::assertSame(200, $middleware->handle($request, static fn () => response('ok'), 'screen', 'query')->getStatusCode());
        $request->server->set('REMOTE_ADDR', '203.0.113.99');
        $this->expectException(TooManyRequestsHttpException::class);
        $middleware->handle($request, static fn () => response('must not run'), 'screen', 'query');
    }

    public function testBearerAccountsHaveSeparateBudgetsAndShareTheGlobalBudget(): void
    {
        $context = CreateApiAuthenticationContext::create();
        $request = Request::create('/api/v1/wiki/wikis/ja', 'GET');
        $request->attributes->set(ApiAuthenticationContext::class, $context);
        $first = CreateAccountContext::create($context->identityIdentifier(), $context->initialAccountIdentifier());
        $second = CreateAccountContext::create($context->identityIdentifier(), new AccountIdentifier((string) Str::uuid7()));
        $limiter = new RateLimiter(new Repository(new ArrayStore()));
        config()->set('api_rate_limit.global.query', 2);
        config()->set('api_rate_limit.surfaces.screen.account.query', 1);
        /** @var AuthServiceInterface&MockInterface $authService */
        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldNotReceive('isCurrentSessionValid');
        $middleware = new EnforceApiRateLimit(new ApiRateLimiter($limiter), $authService, app(AccountResolver::class), app(AuthContextCache::class));
        foreach ([$first, $second] as $account) {
            app()->instance(AccountContext::class, $account);
            self::assertSame(200, $middleware->handle($request, static fn () => response('ok'), 'screen', 'query')->getStatusCode());
            self::assertSame(1, $limiter->attempts('api-rate-limit:screen:account:' . $account->principal()->accountIdentifier() . ':query'));
        }
        self::assertSame(2, $limiter->attempts('api-rate-limit:global:query'));
        app()->instance(AccountContext::class, CreateAccountContext::create($context->identityIdentifier(), new AccountIdentifier((string) Str::uuid7())));
        $this->expectException(TooManyRequestsHttpException::class);
        $middleware->handle($request, static fn () => response('must not run'), 'screen', 'query');
    }

    public function testUnauthenticatedRequestsUseTheIpLimit(): void
    {
        $middleware = $this->middleware(new RateLimiter(new Repository(new ArrayStore())));
        $request = Request::create('/api/v1/wiki/wikis/ja', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);

        $response = $middleware->handle($request, static fn () => response('ok'), 'screen', 'query');
        $this->assertSame(200, $response->getStatusCode());

        $this->expectException(TooManyRequestsHttpException::class);
        $middleware->handle($request, static fn () => response('must not run'), 'screen', 'query');
    }

    public function testAcceptedRequestRemainsCountedWhenTheActionFails(): void
    {
        $middleware = $this->middleware(new RateLimiter(new Repository(new ArrayStore())));
        $request = Request::create('/api/v1/site-management/contact/submit', 'POST', server: [
            'REMOTE_ADDR' => '203.0.113.20',
        ]);

        $exception = $this->actionException(fn () => $middleware->handle(
            $request,
            static function (): never {
                throw new RuntimeException('Action failed.');
            },
            'screen',
            'command',
        ));
        $this->assertSame('Action failed.', $exception->getMessage());

        $this->expectException(TooManyRequestsHttpException::class);
        $middleware->handle($request, static fn () => response('must not run'), 'screen', 'command');
    }

    public function testRedisFailurePropagatesWithoutRunningTheAction(): void
    {
        /** @var RateLimiter&MockInterface $rateLimiter */
        $rateLimiter = Mockery::mock(RateLimiter::class);
        $rateLimiter->shouldReceive('tooManyAttempts')->andThrow(new RedisException('Connection refused'));

        $middleware = $this->middleware($rateLimiter);
        $request = Request::create('/api/v1/wiki/wikis/ja', 'GET');

        $this->expectException(RedisException::class);
        $middleware->handle($request, static function (): never {
            self::fail('The Action must not run when Redis fails.');
        }, 'screen', 'query');
    }

    /** @param callable(): mixed $action */
    private function actionException(callable $action): RuntimeException
    {
        try {
            $action();
        } catch (RuntimeException $exception) {
            return $exception;
        }

        self::fail('The simulated Action failure must be thrown.');
    }

    private function middleware(RateLimiter $rateLimiter): EnforceApiRateLimit
    {
        return new EnforceApiRateLimit(
            new ApiRateLimiter($rateLimiter),
            app(AuthServiceInterface::class),
            app(AccountResolver::class),
            app(AuthContextCache::class),
        );
    }
}
