<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Identity\Infrastructure\Service\StepUpAuthenticationStorageService;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class StepUpAuthenticationStorageServiceTest extends TestCase
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
        $app['config']->set('database.redis.default', ['host' => getenv('REDIS_HOST') ?: 'redis','password' => null,'port' => 6379,'database' => 0]);
    }

    public function testItReusesAnIdentityScopeAndSessionBoundAuthorizationUntilItExpires(): void
    {
        $this->setSessionId('session-a');
        $storage = $this->app->make(StepUpAuthenticationStorageServiceInterface::class);
        $this->assertInstanceOf(StepUpAuthenticationStorageService::class, $storage);
        $identity = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $authentication = new StepUpAuthentication($identity, StepUpAuthenticationMethod::PASSKEY, new DateTimeImmutable(), StepUpAuthenticationScope::PASSKEY_MANAGE, new DateTimeImmutable('+10 minutes'));
        $storage->store($authentication);
        $this->assertSame((string) $authentication->identityIdentifier, (string) $storage->requireValid($identity, StepUpAuthenticationScope::PASSKEY_MANAGE)->identityIdentifier);
        $this->assertSame((string) $authentication->identityIdentifier, (string) $storage->requireValid($identity, StepUpAuthenticationScope::PASSKEY_MANAGE)->identityIdentifier);
    }

    public function testItRejectsAuthorizationCreatedInAnotherLoginSession(): void
    {
        $this->setSessionId('session-a');
        $storage = $this->app->make(StepUpAuthenticationStorageServiceInterface::class);
        $identity = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $storage->store(new StepUpAuthentication($identity, StepUpAuthenticationMethod::PASSKEY, new DateTimeImmutable(), StepUpAuthenticationScope::PASSKEY_MANAGE, new DateTimeImmutable('+10 minutes')));

        $this->setSessionId('session-b');

        $this->expectException(StepUpAuthenticationRequiredException::class);
        $storage->requireValid($identity, StepUpAuthenticationScope::PASSKEY_MANAGE);
    }

    public function testItRejectsAnotherIdentity(): void
    {
        $this->setSessionId('session-a');
        $storage = $this->app->make(StepUpAuthenticationStorageServiceInterface::class);
        $storage->store(new StepUpAuthentication(new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001'), StepUpAuthenticationMethod::SSO, new DateTimeImmutable(), StepUpAuthenticationScope::PASSKEY_MANAGE, new DateTimeImmutable('+10 minutes')));
        $this->expectException(StepUpAuthenticationRequiredException::class);
        $storage->requireValid(new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174002'), StepUpAuthenticationScope::PASSKEY_MANAGE);
    }

    public function testItRejectsExpiredAuthorization(): void
    {
        $identity = '123e4567-e89b-72d3-a456-426614174001';
        $this->setSessionId('session-a');
        Redis::set('step_up_authentication:'.$identity.':passkey.manage:session-a', json_encode(['identity_id' => $identity,'method' => 'passkey','verified_at' => (new DateTimeImmutable('-20 minutes'))->format(DATE_ATOM),'scope' => 'passkey.manage','expires_at' => (new DateTimeImmutable('-10 minutes'))->format(DATE_ATOM)], JSON_THROW_ON_ERROR));
        $this->expectException(StepUpAuthenticationRequiredException::class);
        $this->app->make(StepUpAuthenticationStorageServiceInterface::class)->requireValid(new IdentityIdentifier($identity), StepUpAuthenticationScope::PASSKEY_MANAGE);
    }

    private function setSessionId(string $sessionId): void
    {
        $session = new Store('test', new ArraySessionHandler(120), $sessionId);
        $this->app->make('request')->setLaravelSession($session);
    }
}
