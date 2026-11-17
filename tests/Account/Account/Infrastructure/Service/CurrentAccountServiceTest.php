<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Source\Account\Account\Application\Service\CurrentAccount;
use Source\Account\Account\Infrastructure\Service\CurrentAccountService;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Factory\AuthorizationConnectionFactory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Tests\Helper\CreateApiAuthenticationContext;
use Tests\TestCase;

class CurrentAccountServiceTest extends TestCase
{
    public function testOAuthStateIsIsolatedByConnectionAndSurvivesSourceTokenRotation(): void
    {
        $identity = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $account = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000002');
        $principal = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000003');
        $factory = new AuthorizationConnectionFactory(new UuidGenerator());
        $create = static fn () => $factory->create($identity, new OAuthClientIdentifier('019c9b4c-0000-4000-8000-000000000004'), [AuthorizationScope::WikiRead], $account, new AccountPrincipalIdentifier((string) $principal), 'https://mcp.test/mcp', new DateTimeImmutable());
        $first = $create();
        $second = $create();
        $state = [];
        Redis::shouldReceive('set')->andReturnUsing(static function (string $key, string $value) use (&$state): void {
            $state[$key] = $value;
        });
        Redis::shouldReceive('get')->andReturnUsing(static function (string $key) use (&$state): ?string {
            return $state[$key] ?? null;
        });
        $service = new CurrentAccountService(new NullLogger());
        $selected = new CurrentAccount($identity, $account, $principal, $account, $principal, null);
        $cookie = new CurrentAccount($identity, $account, $principal, new AccountIdentifier('019c9b4c-0000-7000-8000-000000000005'), $principal, new DelegationIdentifier('019c9b4c-0000-7000-8000-000000000006'));
        $service->save($cookie);
        request()->attributes->set(ApiAuthenticationContext::class, CreateApiAuthenticationContext::fromConnection($first));
        $service->save($selected);
        request()->attributes->set(ApiAuthenticationContext::class, CreateApiAuthenticationContext::fromConnection($second));
        self::assertNull($service->find($identity));
        $service->save($cookie);
        request()->attributes->remove(ApiAuthenticationContext::class);
        self::assertEquals($cookie, $service->find($identity));
        request()->attributes->set(ApiAuthenticationContext::class, CreateApiAuthenticationContext::fromConnection($first, 'rotated-token'));
        self::assertEquals($selected, $service->find($identity));
    }

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
        Redis::shouldReceive('command')->once()->with('del', ['current-account:' . $identityIdentifier . ':stateless'])->andThrow($exception);
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with('Failed to clear current account context.', ['exception' => $exception]);
        (new CurrentAccountService($logger))->forget($identityIdentifier);
    }

    public function testDefersDeletionUntilCommit(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $session = $this->app()['session.store'];
        $this->app()['request']->setLaravelSession($session);
        $session->setId(str_repeat('a', 40));
        $state = (object) ['called' => false];
        Redis::shouldReceive('command')->once()->with('del', ['current-account:' . $identityIdentifier . ':' . str_repeat('a', 40)])->andReturnUsing(static function () use ($state): void {
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
        $session->setId(str_repeat('b', 40));
        $callback();
        $this->assertTrue($state->called);
    }
}
