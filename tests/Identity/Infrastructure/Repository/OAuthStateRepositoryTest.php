<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Infrastructure\Repository\OAuthStateRepository;
use Tests\TestCase;

class OAuthStateRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }

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
        $repository = $this->app->make(OAuthStateRepositoryInterface::class);

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

        $repository = $this->app->make(OAuthStateRepositoryInterface::class);
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

        $repository = $this->app->make(OAuthStateRepositoryInterface::class);

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

        $repository = $this->app->make(OAuthStateRepositoryInterface::class);

        $this->expectException(InvalidOAuthStateException::class);
        $repository->store($state);
    }
}
