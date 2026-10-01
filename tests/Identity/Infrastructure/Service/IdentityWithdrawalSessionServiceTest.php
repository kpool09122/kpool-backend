<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Infrastructure\Service\IdentityWithdrawalSessionService;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class IdentityWithdrawalSessionServiceTest extends TestCase
{
    public function testTerminatesImmediatelyWithoutTransaction(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        /** @var AuthServiceInterface&MockInterface $authService */
        $authService = Mockery::mock(AuthServiceInterface::class);
        $terminated = false;
        $authService->shouldReceive('invalidateAllSessions')->once()->with($identityIdentifier)->globally()->ordered();
        $authService->shouldReceive('logout')->once()->andReturnUsing(static function () use (&$terminated): void {
            $terminated = true;
        })->globally()->ordered();
        $service = new IdentityWithdrawalSessionService($authService, new NullLogger());

        DB::commit();

        try {
            $this->assertSame(0, DB::transactionLevel());
            $service->terminate($identityIdentifier);
            $this->assertTrue($terminated);
        } finally {
            DB::beginTransaction();
        }
    }

    public function testRollbackDoesNotTerminateSessions(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        /** @var AuthServiceInterface&MockInterface $authService */
        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldNotReceive('invalidateAllSessions');
        $authService->shouldNotReceive('logout');
        $service = new IdentityWithdrawalSessionService($authService, new NullLogger());

        DB::beginTransaction();
        $service->terminate($identityIdentifier);
        DB::rollBack();

        try {
            DB::commit();
            $this->assertSame(0, DB::transactionLevel());
        } finally {
            DB::beginTransaction();
        }
    }

    public function testImmediateFailuresAreLoggedAndLogoutIsStillAttempted(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $invalidationFailure = new RuntimeException('Redis unavailable');
        $logoutFailure = new RuntimeException('Session backend unavailable');
        /** @var AuthServiceInterface&MockInterface $authService */
        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('invalidateAllSessions')->once()->with($identityIdentifier)->andThrow($invalidationFailure)->globally()->ordered();
        $authService->shouldReceive('logout')->once()->andThrow($logoutFailure)->globally()->ordered();
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once()->with('Withdrawn identity session invalidation failed.', ['exception' => $invalidationFailure]);
        $logger->shouldReceive('error')->once()->with('Withdrawn identity logout failed.', ['exception' => $logoutFailure]);
        $service = new IdentityWithdrawalSessionService($authService, $logger);

        DB::commit();

        try {
            $service->terminate($identityIdentifier);
            $this->assertSame(0, DB::transactionLevel());
        } finally {
            DB::beginTransaction();
        }
    }
}
