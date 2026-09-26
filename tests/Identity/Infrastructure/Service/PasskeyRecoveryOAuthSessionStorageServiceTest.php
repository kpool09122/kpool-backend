<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSession;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Infrastructure\Service\PasskeyRecoveryOAuthSessionStorageService;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class PasskeyRecoveryOAuthSessionStorageServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Redis::del('passkey_recovery_oauth_session:passkey-recovery-state', 'passkey_recovery_oauth_session:expired-state');
        parent::tearDown();
    }

    #[\Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('database.redis.client', 'phpredis');
        $app['config']->set('database.redis.default', ['host' => getenv('REDIS_HOST') ?: 'redis', 'password' => null, 'port' => 6379, 'database' => 0]);
    }

    public function testItConsumesAnOAuthSessionExactlyOnce(): void
    {
        $storage = new PasskeyRecoveryOAuthSessionStorageService();
        $state = new OAuthState('passkey-recovery-state', new DateTimeImmutable('+10 minutes'));
        $storage->store($state, new PasskeyRecoveryOAuthSession(new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000'), SocialProvider::GOOGLE, new DateTimeImmutable('+10 minutes')));
        $this->assertNotNull($storage->consume($state));
        $this->assertNull($storage->consume($state));
    }

    public function testItRejectsAndConsumesAnExpiredOAuthSession(): void
    {
        $state = new OAuthState('expired-state', new DateTimeImmutable('+10 minutes'));
        Redis::set('passkey_recovery_oauth_session:expired-state', json_encode(['identity_id' => '123e4567-e89b-72d3-a456-426614174000', 'provider' => 'google', 'expires_at' => (new DateTimeImmutable('-1 second'))->format(DATE_ATOM)], JSON_THROW_ON_ERROR));
        $storage = new PasskeyRecoveryOAuthSessionStorageService();
        $this->assertNull($storage->consume($state));
        $this->assertNull($storage->consume($state));
    }
}
