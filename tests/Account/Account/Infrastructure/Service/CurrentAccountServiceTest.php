<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Source\Account\Account\Application\Service\CurrentAccount;
use Source\Account\Account\Infrastructure\Service\CurrentAccountService;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class CurrentAccountServiceTest extends TestCase
{
    public function testSavesAndRestoresDelegatedAndOriginalContexts(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $currentAccountService = new CurrentAccountService(new NullLogger());
        foreach ([null, new DelegationIdentifier('019c9b4c-0000-7000-8000-000000000006')] as $delegationIdentifier) {
            $currentAccount = new CurrentAccount($identityIdentifier, new AccountIdentifier('019c9b4c-0000-7000-8000-000000000002'), new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000003'), new AccountIdentifier('019c9b4c-0000-7000-8000-000000000004'), new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000005'), $delegationIdentifier);
            $payload = null;
            $key = 'current-account:' . $identityIdentifier . ':stateless';
            Redis::shouldReceive('set')->once()->with($key, Mockery::type('string'))->andReturnUsing(static function (string $key, string $value) use (&$payload): void {
                $payload = $value;
            });
            $currentAccountService->save($currentAccount);
            Redis::shouldReceive('get')->once()->with($key)->andReturn($payload);
            $this->assertEquals($currentAccount, $currentAccountService->find($identityIdentifier));
        }
    }

    public function testInvalidOrMissingCachePayloadReturnsNull(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        foreach ([null, '', 'invalid-json', '[]', '{"originalIdentityIdentifier":"missing-fields"}'] as $payload) {
            Redis::shouldReceive('get')->once()->andReturn($payload);
            $this->assertNull((new CurrentAccountService(new NullLogger()))->find($identityIdentifier));
        }
    }

    public function testReadFailureReturnsNull(): void
    {
        Redis::shouldReceive('get')->once()->andThrow(new RuntimeException('unavailable'));
        $this->assertNull((new CurrentAccountService(new NullLogger()))->find(new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001')));
    }

    public function testForgetsImmediatelyAndLogsCacheFailure(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $exception = new RuntimeException('unavailable');
        Redis::shouldReceive('del')->once()->with('current-account:' . $identityIdentifier . ':stateless')->andThrow($exception);
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with('Failed to clear current account context.', ['exception' => $exception]);
        (new CurrentAccountService($logger))->forget($identityIdentifier);
    }

    public function testDefersDeletionUntilCommit(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $state = (object) ['called' => false];
        Redis::shouldReceive('del')->once()->andReturnUsing(static function () use ($state): void {
            $state->called = true;
        });
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        $callback = null;
        DB::shouldReceive('afterCommit')->once()->andReturnUsing(static function (callable $afterCommit) use (&$callback): void {
            $callback = $afterCommit;
        });
        (new CurrentAccountService(new NullLogger()))->forget($identityIdentifier);
        $this->assertFalse($state->called);
        $this->assertIsCallable($callback);
        $callback();
        $this->assertTrue($state->called);
    }
}
