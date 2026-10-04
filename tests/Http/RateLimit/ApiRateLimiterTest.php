<?php

declare(strict_types=1);

namespace Tests\Http\RateLimit;

use Application\Http\Exceptions\TooManyRequestsHttpException;
use Application\Http\RateLimit\ApiRateLimiter;
use Application\Http\RateLimit\RateLimitOperation;
use Application\Http\RateLimit\RateLimitSubject;
use Application\Http\RateLimit\RateLimitSurface;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ApiRateLimiterTest extends TestCase
{
    private ?RateLimiter $rateLimiter = null;
    private ?ApiRateLimiter $apiRateLimiter = null;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('api_rate_limit', [
            'decay_seconds' => 60,
            'global' => ['query' => 10, 'command' => 10],
            'surfaces' => [
                'screen' => [
                    'account' => ['query' => 2, 'command' => 2],
                    'ip' => ['query' => 2, 'command' => 2],
                ],
                'public_api' => [
                    'account' => ['query' => 2, 'command' => 2],
                    'ip' => ['query' => 2, 'command' => 2],
                ],
            ],
        ]);

        $this->rateLimiter = new RateLimiter(new Repository(new ArrayStore()));
        $this->apiRateLimiter = new ApiRateLimiter($this->rateLimiter);
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 0, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testAllowsMaximumRequestsAndRejectsNextWithoutChangingAnyCounter(): void
    {
        $subject = RateLimitSubject::account('account-a');

        $this->apiRateLimiter()->hit(RateLimitSurface::SCREEN, RateLimitOperation::QUERY, $subject);
        $this->apiRateLimiter()->hit(RateLimitSurface::SCREEN, RateLimitOperation::QUERY, $subject);

        $exception = $this->rateLimitException(fn () => $this->apiRateLimiter()->hit(
            RateLimitSurface::SCREEN,
            RateLimitOperation::QUERY,
            $subject,
        ));
        $this->assertSame(60, $exception->retryAfter);

        $this->assertSame(2, $this->rateLimiter()->attempts('api-rate-limit:global:query'));
        $this->assertSame(2, $this->rateLimiter()->attempts('api-rate-limit:screen:account:account-a:query'));
    }

    public function testSubjectRejectionDoesNotConsumeGlobalLimitOrAnotherAccountLimit(): void
    {
        config()->set('api_rate_limit.surfaces.screen.account.query', 1);

        $this->apiRateLimiter()->hit(
            RateLimitSurface::SCREEN,
            RateLimitOperation::QUERY,
            RateLimitSubject::account('account-a'),
        );

        $this->rateLimitException(fn () => $this->apiRateLimiter()->hit(
            RateLimitSurface::SCREEN,
            RateLimitOperation::QUERY,
            RateLimitSubject::account('account-a'),
        ));

        $this->assertSame(1, $this->rateLimiter()->attempts('api-rate-limit:global:query'));
        $this->assertSame(0, $this->rateLimiter()->attempts('api-rate-limit:screen:account:account-b:query'));

        $this->apiRateLimiter()->hit(
            RateLimitSurface::SCREEN,
            RateLimitOperation::QUERY,
            RateLimitSubject::account('account-b'),
        );
        $this->assertSame(2, $this->rateLimiter()->attempts('api-rate-limit:global:query'));
    }

    public function testRetryAfterWaitsUntilBothExceededLimitsExpireWithoutChangingAnyCounter(): void
    {
        config()->set('api_rate_limit.global.query', 2);
        config()->set('api_rate_limit.surfaces.screen.account.query', 1);

        $this->apiRateLimiter()->hit(
            RateLimitSurface::SCREEN,
            RateLimitOperation::QUERY,
            RateLimitSubject::account('account-a'),
        );

        Carbon::setTestNow(Carbon::now()->addSeconds(30));
        $subject = RateLimitSubject::account('account-b');
        $this->apiRateLimiter()->hit(RateLimitSurface::SCREEN, RateLimitOperation::QUERY, $subject);

        Carbon::setTestNow(Carbon::now()->addSeconds(10));
        $exception = $this->rateLimitException(fn () => $this->apiRateLimiter()->hit(
            RateLimitSurface::SCREEN,
            RateLimitOperation::QUERY,
            $subject,
        ));

        $this->assertSame(50, $exception->retryAfter);
        $this->assertSame(['Retry-After' => '50'], $exception->getHeaders());
        $this->assertSame(2, $this->rateLimiter()->attempts('api-rate-limit:global:query'));
        $this->assertSame(1, $this->rateLimiter()->attempts('api-rate-limit:screen:account:account-b:query'));

        Carbon::setTestNow(Carbon::now()->addSeconds($exception->retryAfter));
        $this->apiRateLimiter()->hit(RateLimitSurface::SCREEN, RateLimitOperation::QUERY, $subject);

        $this->assertSame(1, $this->rateLimiter()->attempts('api-rate-limit:global:query'));
        $this->assertSame(1, $this->rateLimiter()->attempts('api-rate-limit:screen:account:account-b:query'));
    }

    public function testQueryAndCommandAreIndependent(): void
    {
        $subject = RateLimitSubject::account('account-a');

        $this->apiRateLimiter()->hit(RateLimitSurface::SCREEN, RateLimitOperation::QUERY, $subject);
        $this->apiRateLimiter()->hit(RateLimitSurface::SCREEN, RateLimitOperation::COMMAND, $subject);

        $this->assertSame(1, $this->rateLimiter()->attempts('api-rate-limit:global:query'));
        $this->assertSame(1, $this->rateLimiter()->attempts('api-rate-limit:global:command'));
        $this->assertSame(1, $this->rateLimiter()->attempts('api-rate-limit:screen:account:account-a:query'));
        $this->assertSame(1, $this->rateLimiter()->attempts('api-rate-limit:screen:account:account-a:command'));
    }

    public function testScreenAndPublicApiSubjectLimitsAreIndependentButGlobalLimitIsShared(): void
    {
        config()->set('api_rate_limit.global.query', 2);
        $subject = RateLimitSubject::account('account-a');

        $this->apiRateLimiter()->hit(RateLimitSurface::SCREEN, RateLimitOperation::QUERY, $subject);
        $this->apiRateLimiter()->hit(RateLimitSurface::PUBLIC_API, RateLimitOperation::QUERY, $subject);

        $this->assertSame(1, $this->rateLimiter()->attempts('api-rate-limit:screen:account:account-a:query'));
        $this->assertSame(1, $this->rateLimiter()->attempts('api-rate-limit:public_api:account:account-a:query'));
        $this->assertSame(2, $this->rateLimiter()->attempts('api-rate-limit:global:query'));

        $this->expectException(TooManyRequestsHttpException::class);
        $this->apiRateLimiter()->hit(
            RateLimitSurface::SCREEN,
            RateLimitOperation::QUERY,
            RateLimitSubject::account('account-b'),
        );
    }

    public function testWindowExpiresSixtySecondsAfterFirstAcceptedRequestAndRejectedRequestsDoNotExtendIt(): void
    {
        config()->set('api_rate_limit.surfaces.screen.ip.query', 1);
        $subject = RateLimitSubject::ip('203.0.113.10');

        $this->apiRateLimiter()->hit(RateLimitSurface::SCREEN, RateLimitOperation::QUERY, $subject);
        Carbon::setTestNow(Carbon::now()->addSeconds(30));

        $exception = $this->rateLimitException(fn () => $this->apiRateLimiter()->hit(
            RateLimitSurface::SCREEN,
            RateLimitOperation::QUERY,
            $subject,
        ));
        $this->assertSame(30, $exception->retryAfter);

        Carbon::setTestNow(Carbon::now()->addSeconds(31));
        $this->apiRateLimiter()->hit(RateLimitSurface::SCREEN, RateLimitOperation::QUERY, $subject);

        $this->assertSame(1, $this->rateLimiter()->attempts(
            'api-rate-limit:screen:ip:' . hash('sha256', '203.0.113.10') . ':query',
        ));
    }

    private function rateLimiter(): RateLimiter
    {
        $rateLimiter = $this->rateLimiter;
        self::assertNotNull($rateLimiter);

        return $rateLimiter;
    }

    private function apiRateLimiter(): ApiRateLimiter
    {
        $apiRateLimiter = $this->apiRateLimiter;
        self::assertNotNull($apiRateLimiter);

        return $apiRateLimiter;
    }

    /** @param callable(): void $request */
    private function rateLimitException(callable $request): TooManyRequestsHttpException
    {
        try {
            $request();
        } catch (TooManyRequestsHttpException $exception) {
            return $exception;
        }

        self::fail('The request must be rate limited.');
    }
}
