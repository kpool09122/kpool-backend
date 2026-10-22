<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Redis;
use Override;
use Source\Identity\Application\Service\OAuthStateSession;
use Source\Identity\Application\Service\OAuthStateStorageServiceInterface;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Infrastructure\Service\OAuthStateStorageService;
use Tests\TestCase;

class OAuthStateStorageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app()->make('request')->setLaravelSession(new Store('test', new ArraySessionHandler(120), 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'));
    }

    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }

    #[Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.redis.client', 'phpredis');
        $app['config']->set('database.redis.default', [
            'host' => getenv('REDIS_HOST') ?: 'redis',
            'password' => null,
            'port' => 6379,
            'database' => 0,
        ]);
    }

    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $oAuthStateStorageService = $this->app()->make(OAuthStateStorageServiceInterface::class);

        $this->assertInstanceOf(OAuthStateStorageService::class, $oAuthStateStorageService);
    }

    /**
     * 正常系: stateを保存し、consumeで消費できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidOAuthStateException
     */
    public function testStoreAndConsume(): void
    {
        $state = new OAuthState('test-state-token');
        $expiresAt = new DateTimeImmutable('+10 minutes');

        $oAuthStateStorageService = $this->app()->make(OAuthStateStorageServiceInterface::class);
        $oAuthStateStorageService->store(new OAuthStateSession($state, $expiresAt));
        $consumed = $oAuthStateStorageService->consume($state);
        $this->assertSame((string) $state, (string) $consumed->state);
        $this->assertSame($expiresAt->getTimestamp(), $consumed->expiresAt->getTimestamp());

        $this->expectException(InvalidOAuthStateException::class);
        $oAuthStateStorageService->consume($state);
    }

    /**
     * 異常系: 存在しないstateをconsumeしようとすると例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidOAuthStateException
     */
    public function testConsumeThrowsExceptionWhenStateNotFound(): void
    {
        $state = new OAuthState('non-existent-state');

        $oAuthStateStorageService = $this->app()->make(OAuthStateStorageServiceInterface::class);

        $this->expectException(InvalidOAuthStateException::class);
        $oAuthStateStorageService->consume($state);
    }

    /**
     * 異常系: 期限切れのstateを保存しようとすると例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidOAuthStateException
     */
    public function testStoreThrowsExceptionWhenStateAlreadyExpired(): void
    {
        $state = new OAuthState('expired-state');

        $oAuthStateStorageService = $this->app()->make(OAuthStateStorageServiceInterface::class);

        $this->expectException(InvalidOAuthStateException::class);
        $oAuthStateStorageService->store(new OAuthStateSession($state, new DateTimeImmutable('-1 minute')));
    }

    public function testAnotherSessionCannotConsumeStateAndOriginatingSessionCanStillConsumeIt(): void
    {
        $state = new OAuthState('session-bound-state');
        $oAuthStateStorageService = $this->app()->make(OAuthStateStorageServiceInterface::class);
        $oAuthStateStorageService->store(new OAuthStateSession($state, new DateTimeImmutable('+10 minutes')));

        $this->app()->make('request')->session()->setId('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');

        try {
            $oAuthStateStorageService->consume($state);
            $this->fail('Another session must not consume the OAuth state.');
        } catch (InvalidOAuthStateException) {
            $this->app()->make('request')->session()->setId('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        }

        $oAuthStateStorageService->consume($state);
        $this->expectException(InvalidOAuthStateException::class);
        $oAuthStateStorageService->consume($state);
    }

    public function testStoreRejectsRequestsWithoutSession(): void
    {
        $oAuthStateStorageService = new OAuthStateStorageService(new Request());

        $this->expectException(InvalidOAuthStateException::class);
        $oAuthStateStorageService->store(new OAuthStateSession(new OAuthState('no-session-state'), new DateTimeImmutable('+10 minutes')));
    }

    public function testConsumeRejectsRequestsWithoutSession(): void
    {
        $state = new OAuthState('no-session-state');
        $this->app()->make(OAuthStateStorageServiceInterface::class)->store(new OAuthStateSession($state, new DateTimeImmutable('+10 minutes')));
        $oAuthStateStorageService = new OAuthStateStorageService(new Request());

        $this->expectException(InvalidOAuthStateException::class);
        $oAuthStateStorageService->consume($state);
    }

    public function testLegacyUnboundStateCannotBeConsumed(): void
    {
        $state = new OAuthState('legacy-state');
        Redis::setex('oauth_state:' . $state, 600, '1');
        $oAuthStateStorageService = $this->app()->make(OAuthStateStorageServiceInterface::class);

        $this->expectException(InvalidOAuthStateException::class);
        $oAuthStateStorageService->consume($state);
    }

    public function testConsumeRejectsAndRemovesAnExpiredSession(): void
    {
        $state = new OAuthState('expired-session');
        $key = 'oauth_state:' . $state . ':aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        Redis::setex($key, 600, json_encode([
            'expires_at' => (new DateTimeImmutable('-1 minute'))->format(DATE_ATOM),
        ], JSON_THROW_ON_ERROR));
        $oAuthStateStorageService = $this->app()->make(OAuthStateStorageServiceInterface::class);

        try {
            $oAuthStateStorageService->consume($state);
            $this->fail('An expired OAuth session must not be accepted.');
        } catch (InvalidOAuthStateException) {
            $this->assertSame(0, Redis::exists($key));
        }
    }
}
