<?php

declare(strict_types=1);

namespace Tests\Auth\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Redis;
use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Auth\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Auth\Domain\ValueObject\AuthCode;
use Source\Auth\Infrastructure\Repository\AuthCodeSessionRepository;
use Source\Shared\Domain\ValueObject\Email;
use Tests\TestCase;

class AuthCodeSessionRepositoryTest extends TestCase
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
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(AuthCodeSessionRepositoryInterface::class);
        $this->assertInstanceOf(AuthCodeSessionRepository::class, $repository);
    }

    /**
     * 正常系: セッションを保存し、メールアドレスで取得できること.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testSaveAndFindByEmail(): void
    {
        $email = new Email('test@example.com');
        $authCode = new AuthCode('123456');
        $generatedAt = new DateTimeImmutable('2024-01-01T12:00:00+00:00');
        $session = new AuthCodeSession($email, $authCode, $generatedAt);

        $repository = $this->app->make(AuthCodeSessionRepositoryInterface::class);
        $repository->save($session);

        $found = $repository->findByEmail($email);

        $this->assertNotNull($found);
        $this->assertSame((string) $email, (string) $found->email());
        $this->assertSame((string) $authCode, (string) $found->authCode());
        $this->assertEquals($generatedAt, $found->generatedAt());
        $this->assertNull($found->verifiedAt());
    }

    /**
     * 正常系: verifiedAt付きのセッションを保存し、取得できること.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testSaveAndFindByEmailWithVerifiedAt(): void
    {
        $email = new Email('verified@example.com');
        $authCode = new AuthCode('654321');
        $generatedAt = new DateTimeImmutable('2024-01-01T12:00:00+00:00');
        $verifiedAt = new DateTimeImmutable('2024-01-01T12:05:00+00:00');
        $session = new AuthCodeSession($email, $authCode, $generatedAt, $verifiedAt);

        $repository = $this->app->make(AuthCodeSessionRepositoryInterface::class);
        $repository->save($session);

        $found = $repository->findByEmail($email);

        $this->assertNotNull($found);
        $this->assertEquals($verifiedAt, $found->verifiedAt());
    }

    /**
     * 正常系: 存在しないメールアドレスで検索した場合にnullを返すこと.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $email = new Email('notfound@example.com');

        $repository = $this->app->make(AuthCodeSessionRepositoryInterface::class);
        $found = $repository->findByEmail($email);

        $this->assertNull($found);
    }

    /**
     * 正常系: セッションを削除できること.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testDelete(): void
    {
        $email = new Email('delete@example.com');
        $authCode = new AuthCode('111111');
        $generatedAt = new DateTimeImmutable();
        $session = new AuthCodeSession($email, $authCode, $generatedAt);

        $repository = $this->app->make(AuthCodeSessionRepositoryInterface::class);
        $repository->save($session);

        $this->assertNotNull($repository->findByEmail($email));

        $repository->delete($email);

        $this->assertNull($repository->findByEmail($email));
    }

    /**
     * 正常系: 同一メールアドレスで保存した場合、上書きされること.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testSaveOverwritesExistingSession(): void
    {
        $email = new Email('overwrite@example.com');
        $authCode1 = new AuthCode('111111');
        $authCode2 = new AuthCode('222222');
        $generatedAt = new DateTimeImmutable();

        $session1 = new AuthCodeSession($email, $authCode1, $generatedAt);
        $repository = $this->app->make(AuthCodeSessionRepositoryInterface::class);
        $repository->save($session1);

        $session2 = new AuthCodeSession($email, $authCode2, $generatedAt);
        $repository->save($session2);

        $found = $repository->findByEmail($email);

        $this->assertNotNull($found);
        $this->assertSame('222222', (string) $found->authCode());
    }
}
