<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Redis;
use Override;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Infrastructure\Repository\OAuthStateRepository;
use Tests\TestCase;

class OAuthStateRepositoryTest extends TestCase
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
        $repository = $this->app()->make(OAuthStateRepositoryInterface::class);

        $this->assertInstanceOf(OAuthStateRepository::class, $repository);
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
        $state = new OAuthState('test-state-token', new DateTimeImmutable('+10 minutes'));

        $repository = $this->app()->make(OAuthStateRepositoryInterface::class);
        $repository->store($state);
        $repository->consume($state);

        $this->expectException(InvalidOAuthStateException::class);
        $repository->consume($state);
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
        $state = new OAuthState('non-existent-state', new DateTimeImmutable('+10 minutes'));

        $repository = $this->app()->make(OAuthStateRepositoryInterface::class);

        $this->expectException(InvalidOAuthStateException::class);
        $repository->consume($state);
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
        $state = new OAuthState('expired-state', new DateTimeImmutable('-1 minute'));

        $repository = $this->app()->make(OAuthStateRepositoryInterface::class);

        $this->expectException(InvalidOAuthStateException::class);
        $repository->store($state);
    }

    public function testAnotherSessionCannotConsumeStateAndOriginatingSessionCanStillConsumeIt(): void
    {
        $state = new OAuthState('session-bound-state', new DateTimeImmutable('+10 minutes'));
        $repository = $this->app()->make(OAuthStateRepositoryInterface::class);
        $repository->store($state);

        $this->app()->make('request')->session()->setId('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');

        try {
            $repository->consume($state);
            $this->fail('Another session must not consume the OAuth state.');
        } catch (InvalidOAuthStateException) {
            $this->app()->make('request')->session()->setId('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        }

        $repository->consume($state);
        $this->expectException(InvalidOAuthStateException::class);
        $repository->consume($state);
    }

    public function testStoreRejectsRequestsWithoutSession(): void
    {
        $repository = new OAuthStateRepository(new Request());

        $this->expectException(InvalidOAuthStateException::class);
        $repository->store(new OAuthState('no-session-state', new DateTimeImmutable('+10 minutes')));
    }

    public function testResolvedRepositoryUsesTheCurrentRequestSession(): void
    {
        $state = new OAuthState('new-request-state', new DateTimeImmutable('+10 minutes'));
        $this->app()->make(OAuthStateRepositoryInterface::class)->store($state);

        $request = new Request();
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120), 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'));
        $this->app()->instance('request', $request);

        $repository = $this->app()->make(OAuthStateRepositoryInterface::class);
        $this->expectException(InvalidOAuthStateException::class);
        $repository->consume($state);
    }

    public function testConsumeRejectsRequestsWithoutSession(): void
    {
        $state = new OAuthState('no-session-state', new DateTimeImmutable('+10 minutes'));
        $this->app()->make(OAuthStateRepositoryInterface::class)->store($state);
        $repository = new OAuthStateRepository(new Request());

        $this->expectException(InvalidOAuthStateException::class);
        $repository->consume($state);
    }

    public function testLegacyUnboundStateCannotBeConsumed(): void
    {
        $state = new OAuthState('legacy-state', new DateTimeImmutable('+10 minutes'));
        Redis::setex('oauth_state:' . $state, 600, '1');
        $repository = $this->app()->make(OAuthStateRepositoryInterface::class);

        $this->expectException(InvalidOAuthStateException::class);
        $repository->consume($state);
    }
}
