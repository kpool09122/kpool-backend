<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\StepUpOAuthSessionStorageServiceInterface;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Identity\Domain\ValueObject\StepUpOAuthSession;
use Source\Identity\Infrastructure\Service\StepUpOAuthSessionStorageService;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class StepUpOAuthSessionStorageServiceTest extends TestCase
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
        $app['config']->set('database.redis.default', ['host' => getenv('REDIS_HOST') ?: 'redis', 'password' => null, 'port' => 6379, 'database' => 0]);
    }

    public function testItConsumesASessionExactlyOnce(): void
    {
        $storage = $this->app->make(StepUpOAuthSessionStorageServiceInterface::class);
        $this->assertInstanceOf(StepUpOAuthSessionStorageService::class, $storage);
        $state = new OAuthState('step-up-state', new DateTimeImmutable('+10 minutes'));
        $storage->store($state, new StepUpOAuthSession(
            new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001'),
            SocialProvider::GOOGLE,
            StepUpAuthenticationScope::PASSKEY_MANAGE,
            new DateTimeImmutable('+10 minutes'),
            '/settings/passkeys',
        ));

        $consumed = $storage->consume($state);

        $this->assertNotNull($consumed);
        $this->assertSame('123e4567-e89b-72d3-a456-426614174001', (string) $consumed->identityIdentifier);
        $this->assertNull($storage->consume($state));
    }

    public function testItRejectsAndConsumesAnExpiredSession(): void
    {
        $state = new OAuthState('step-up-expired', new DateTimeImmutable('+10 minutes'));
        Redis::set('step_up_oauth_session:step-up-expired', json_encode([
            'identity_id' => '123e4567-e89b-72d3-a456-426614174001',
            'provider' => 'google',
            'scope' => 'passkey.manage',
            'expires_at' => (new DateTimeImmutable('-1 second'))->format(DATE_ATOM),
            'return_to' => '/settings/passkeys',
        ], JSON_THROW_ON_ERROR));
        $storage = $this->app->make(StepUpOAuthSessionStorageServiceInterface::class);

        $this->assertNull($storage->consume($state));
        $this->assertNull($storage->consume($state));
    }
}
