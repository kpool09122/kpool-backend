<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\Service\IdentityWithdrawalServiceInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\WithdrawIdentity\WithdrawIdentity;
use Source\Identity\Application\UseCase\Query\ListPasskeys\ListPasskeysInput;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Identity\Infrastructure\Query\ListPasskeys;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

#[Group('useDb')]
class SharedRecentAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app()->make('request')->setLaravelSession(new Store('test', new ArraySessionHandler(120), 'cccccccccccccccccccccccccccccccccccccccc'));
    }

    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }

    /** @return array<string, array{StepUpAuthenticationMethod}> */
    public static function methods(): array
    {
        return ['passkey' => [StepUpAuthenticationMethod::PASSKEY], 'social' => [StepUpAuthenticationMethod::SSO]];
    }

    #[DataProvider('methods')]
    public function testBothOperationsReuseTheSameResultWithoutExtendingIt(StepUpAuthenticationMethod $method): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $storage = $this->app()->make(StepUpAuthenticationStorageServiceInterface::class);
        $verifiedAt = new DateTimeImmutable('-5 minutes');
        $expiresAt = $verifiedAt->modify('+10 minutes');
        $storage->store(new StepUpAuthentication($identityIdentifier, $method, $verifiedAt, StepUpAuthenticationScope::RECENT_AUTHENTICATION, $expiresAt));
        $key = 'step_up_authentication:' . $identityIdentifier . ':recent_authentication:cccccccccccccccccccccccccccccccccccccccc';
        $original = Redis::get($key);
        /** @var IdentityWithdrawalServiceInterface&MockInterface $identityWithdrawalService */
        $identityWithdrawalService = Mockery::mock(IdentityWithdrawalServiceInterface::class);
        $identityWithdrawalService->shouldReceive('archive')->once()->with($identityIdentifier, Mockery::type(DateTimeImmutable::class));
        $identityWithdrawalService->shouldReceive('delete')->once()->with($identityIdentifier);
        /** @var EventDispatcherInterface&MockInterface $eventDispatcher */
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')->once();
        $listPasskeys = new ListPasskeys($storage);

        $this->assertSame([], $listPasskeys->process(new ListPasskeysInput($identityIdentifier)));
        (new WithdrawIdentity($storage, $identityWithdrawalService, $eventDispatcher))->process($identityIdentifier);
        $this->assertSame([], $listPasskeys->process(new ListPasskeysInput($identityIdentifier)));

        $this->assertSame($original, Redis::get($key));
        $this->assertSame($expiresAt->getTimestamp(), $storage->requireValid($identityIdentifier, StepUpAuthenticationScope::RECENT_AUTHENTICATION)->expiresAt->getTimestamp());
    }

    /** @return array<string, array{string}> */
    public static function invalidResults(): array
    {
        return ['missing' => ['missing'], 'expired' => ['expired'], 'other identity' => ['identity'], 'other session' => ['session']];
    }

    #[DataProvider('invalidResults')]
    public function testInvalidResultsDoNotReachAnyWithdrawalSideEffect(string $condition): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $storage = $this->app()->make(StepUpAuthenticationStorageServiceInterface::class);
        if ($condition !== 'missing') {
            $storedIdentity = $condition === 'identity' ? new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174002') : $identityIdentifier;
            $now = new DateTimeImmutable();
            $storage->store(new StepUpAuthentication($storedIdentity, StepUpAuthenticationMethod::PASSKEY, $now, StepUpAuthenticationScope::RECENT_AUTHENTICATION, $now->modify('+10 minutes')));
            if ($condition === 'session') {
                $this->app()->make('request')->session()->setId('dddddddddddddddddddddddddddddddddddddddd');
            }
            if ($condition === 'expired') {
                Redis::set('step_up_authentication:' . $identityIdentifier . ':recent_authentication:cccccccccccccccccccccccccccccccccccccccc', json_encode([
                    'identity_id' => (string) $identityIdentifier, 'method' => 'passkey', 'scope' => 'recent_authentication',
                    'verified_at' => $now->modify('-10 minutes')->format(DATE_ATOM), 'expires_at' => $now->format(DATE_ATOM),
                ], JSON_THROW_ON_ERROR));
            }
        }
        /** @var IdentityWithdrawalServiceInterface&MockInterface $identityWithdrawalService */
        $identityWithdrawalService = Mockery::mock(IdentityWithdrawalServiceInterface::class);
        $identityWithdrawalService->shouldNotReceive('archive');
        $identityWithdrawalService->shouldNotReceive('delete');
        /** @var EventDispatcherInterface&MockInterface $eventDispatcher */
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldNotReceive('dispatch');

        $this->expectException(StepUpAuthenticationRequiredException::class);
        (new WithdrawIdentity($storage, $identityWithdrawalService, $eventDispatcher))->process($identityIdentifier);
    }
}
