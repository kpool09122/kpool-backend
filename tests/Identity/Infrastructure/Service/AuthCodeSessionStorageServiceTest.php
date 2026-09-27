<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\AuthCodeSessionStorageServiceInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\AuthCodeSession;
use Source\Identity\Infrastructure\Service\AuthCodeSessionStorageService;
use Source\Shared\Domain\ValueObject\Email;
use Tests\TestCase;

class AuthCodeSessionStorageServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }

    #[\Override]
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
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app()->make(AuthCodeSessionStorageServiceInterface::class);
        $this->assertInstanceOf(AuthCodeSessionStorageService::class, $service);
    }

    public function testStoreAndFindByEmailPreservesFormatAndTtl(): void
    {
        $email = new Email('test@example.com');
        $authCode = new AuthCode('123456');
        $generatedAt = new DateTimeImmutable('2024-01-01T12:00:00+00:00');
        $service = $this->app()->make(AuthCodeSessionStorageServiceInterface::class);

        $service->store(new AuthCodeSession($email, $authCode, $generatedAt));

        $key = 'auth_code_session:' . $email;
        $this->assertSame(
            '{"email":"test@example.com","authCode":"123456","generatedAt":"2024-01-01T12:00:00+00:00","verifiedAt":null}',
            Redis::get($key),
        );
        $this->assertContains(Redis::ttl($key), [899, 900]);

        $found = $service->findByEmail($email);
        $this->assertNotNull($found);
        $this->assertSame((string) $email, (string) $found->email());
        $this->assertSame((string) $authCode, (string) $found->authCode());
        $this->assertEquals($generatedAt, $found->generatedAt());
        $this->assertNull($found->verifiedAt());
    }

    public function testFindByEmailRestoresExistingVerifiedSessionFormat(): void
    {
        $email = new Email('verified@example.com');
        Redis::setex('auth_code_session:' . $email, 900, json_encode([
            'email' => 'verified@example.com',
            'authCode' => '654321',
            'generatedAt' => '2024-01-01T12:00:00+00:00',
            'verifiedAt' => '2024-01-01T12:05:00+00:00',
        ]));

        $found = $this->app()->make(AuthCodeSessionStorageServiceInterface::class)->findByEmail($email);

        $this->assertNotNull($found);
        $this->assertEquals(new DateTimeImmutable('2024-01-01T12:05:00+00:00'), $found->verifiedAt());
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $found = $this->app()->make(AuthCodeSessionStorageServiceInterface::class)
            ->findByEmail(new Email('notfound@example.com'));

        $this->assertNull($found);
    }

    public function testDelete(): void
    {
        $email = new Email('delete@example.com');
        $service = $this->app()->make(AuthCodeSessionStorageServiceInterface::class);
        $service->store(new AuthCodeSession($email, new AuthCode('111111'), new DateTimeImmutable()));

        $service->delete($email);

        $this->assertNull($service->findByEmail($email));
    }

    public function testStoreOverwritesExistingSession(): void
    {
        $email = new Email('overwrite@example.com');
        $service = $this->app()->make(AuthCodeSessionStorageServiceInterface::class);
        $generatedAt = new DateTimeImmutable();
        $service->store(new AuthCodeSession($email, new AuthCode('111111'), $generatedAt));
        $service->store(new AuthCodeSession($email, new AuthCode('222222'), $generatedAt));

        $found = $service->findByEmail($email);
        $this->assertNotNull($found);
        $this->assertSame('222222', (string) $found->authCode());
    }
}
