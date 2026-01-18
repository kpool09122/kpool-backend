<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Redis;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Identity\Domain\Repository\SignupSessionRepositoryInterface;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Identity\Infrastructure\Repository\SignupSessionRepository;
use Tests\TestCase;

class SignupSessionRepositoryTest extends TestCase
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
        $repository = $this->app->make(SignupSessionRepositoryInterface::class);

        $this->assertInstanceOf(SignupSessionRepository::class, $repository);
    }

    /**
     * 正常系: SignupSessionを保存し、findで取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testStoreAndFind(): void
    {
        $state = new OAuthState('test-state-token', new DateTimeImmutable('+10 minutes'));
        $session = new SignupSession(AccountType::INDIVIDUAL);

        $repository = $this->app->make(SignupSessionRepositoryInterface::class);
        $repository->store($state, $session);

        $foundSession = $repository->find($state);

        $this->assertNotNull($foundSession);
        $this->assertSame(AccountType::INDIVIDUAL, $foundSession->accountType());
    }

    /**
     * 正常系: AccountType::CORPORATIONのSignupSessionを保存し、取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testStoreAndFindWithCorporation(): void
    {
        $state = new OAuthState('test-state-token', new DateTimeImmutable('+10 minutes'));
        $session = new SignupSession(AccountType::CORPORATION);

        $repository = $this->app->make(SignupSessionRepositoryInterface::class);
        $repository->store($state, $session);

        $foundSession = $repository->find($state);

        $this->assertNotNull($foundSession);
        $this->assertSame(AccountType::CORPORATION, $foundSession->accountType());
    }

    /**
     * 正常系: 存在しないstateでfindするとnullが返ること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFindReturnsNullWhenNotFound(): void
    {
        $state = new OAuthState('non-existent-state', new DateTimeImmutable('+10 minutes'));

        $repository = $this->app->make(SignupSessionRepositoryInterface::class);
        $foundSession = $repository->find($state);

        $this->assertNull($foundSession);
    }

    /**
     * 正常系: deleteでSignupSessionを削除できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testDelete(): void
    {
        $state = new OAuthState('test-state-token', new DateTimeImmutable('+10 minutes'));
        $session = new SignupSession(AccountType::INDIVIDUAL);

        $repository = $this->app->make(SignupSessionRepositoryInterface::class);
        $repository->store($state, $session);
        $repository->delete($state);

        $foundSession = $repository->find($state);
        $this->assertNull($foundSession);
    }

    /**
     * 正常系: Redisに無効なAccountType値が保存されている場合、nullを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFindReturnsNullWhenInvalidAccountTypeStored(): void
    {
        $state = new OAuthState('test-state-token', new DateTimeImmutable('+10 minutes'));
        $key = 'signup_session:' . $state;

        Redis::setex($key, 600, 'invalid_account_type');

        $repository = $this->app->make(SignupSessionRepositoryInterface::class);
        $foundSession = $repository->find($state);

        $this->assertNull($foundSession);
    }
}
