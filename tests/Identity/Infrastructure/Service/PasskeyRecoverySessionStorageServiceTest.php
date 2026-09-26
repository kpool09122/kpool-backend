<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Domain\Exception\PasskeyRecoverySessionInvalidException;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Source\Identity\Infrastructure\Service\PasskeyRecoverySessionStorageService;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class PasskeyRecoverySessionStorageServiceTest extends TestCase
{
    private const string KEY = '123e4567-e89b-72d3-a456-426614174001';

    protected function tearDown(): void
    {
        Redis::del('passkey_recovery_session:' . self::KEY);
        parent::tearDown();
    }

    #[\Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('database.redis.client', 'phpredis');
        $app['config']->set('database.redis.default', ['host' => getenv('REDIS_HOST') ?: 'redis', 'password' => null, 'port' => 6379, 'database' => 0]);
    }

    public function testItConsumesARecoverySessionExactlyOnce(): void
    {
        $identity = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $storage = $this->storage();
        $key = $storage->issue($identity, 'email');
        $storage->consume($key, $identity);
        $this->expectException(PasskeyRecoverySessionInvalidException::class);
        $storage->consume($key, $identity);
    }

    public function testItRejectsAnExpiredRecoverySession(): void
    {
        Redis::set('passkey_recovery_session:' . self::KEY, json_encode(['identity_id' => '123e4567-e89b-72d3-a456-426614174000', 'scope' => 'passkey.recover', 'method' => 'email', 'expires_at' => (new DateTimeImmutable('-1 second'))->format(DATE_ATOM)], JSON_THROW_ON_ERROR));
        $this->expectException(PasskeyRecoverySessionInvalidException::class);
        $this->storage()->requireValid(new PasskeyRecoveryKey(self::KEY));
    }

    private function storage(): PasskeyRecoverySessionStorageService
    {
        /** @var MockInterface&UuidGeneratorInterface $uuid */
        $uuid = Mockery::mock(UuidGeneratorInterface::class);
        $uuid->shouldReceive('generate')->zeroOrMoreTimes()->andReturn(self::KEY);

        return new PasskeyRecoverySessionStorageService($uuid);
    }
}
