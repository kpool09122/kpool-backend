<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

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
use Mockery;
use Mockery\MockInterface;
use RedisException;
use RuntimeException;
use Source\Identity\Domain\Service\AuthServiceInterface;
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
