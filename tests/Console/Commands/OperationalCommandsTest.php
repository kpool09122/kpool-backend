<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Application\Console\Commands\CollectVideoLinksCommand;
use Application\Console\Commands\ProcessDueTransfersCommand;
use Application\Console\Commands\ProcessRolePromotionCommand;
use Application\Jobs\ExecuteTransferJob;
use Application\Jobs\Wiki\CollectVideoLinksJob;
use Application\Jobs\Wiki\ProcessRolePromotionJob;
use DateTimeImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\Repository\TransferRepositoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion\ProcessRolePromotionInterface;
use Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary\UpdateContributionPointSummaryInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class OperationalCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        foreach ([ProcessRolePromotionCommand::class, CollectVideoLinksCommand::class, ProcessDueTransfersCommand::class] as $command) {
            Artisan::registerCommand($this->app()->make($command));
        }
    }

    #[DataProvider('invalidDates')]
    public function testRejectsInvalidDateBeforeQuerying(string $date): void
    {
        $transferRepository = Mockery::mock(TransferRepositoryInterface::class);
        $transferRepository->shouldNotReceive('findDueTransfers');
        $this->app()->instance(TransferRepositoryInterface::class, $transferRepository);
        $this->assertSame(1, $this->runCommand('settlement:process-due-transfers', ['--date' => $date]));
    }

    /** @return array<string, array{string}> */
    public static function invalidDates(): array
    {
        return ['overflow' => ['2026-02-30'], 'relative' => ['tomorrow'], 'time' => ['2026-01-01T12:00:00'], 'empty' => [''], 'unpadded' => ['2026-1-1']];
    }

    public function testNoTransfersAndDryRunSucceedWithoutDispatch(): void
    {
        Queue::fake();
        $transferRepository = Mockery::mock(TransferRepositoryInterface::class);
        $transferRepository->shouldReceive('findDueTransfers')->once()->with(Mockery::on(
            static fn (DateTimeImmutable $date): bool => $date->format('Y-m-d H:i:s e') === '2026-02-28 00:00:00 Asia/Tokyo'
        ))->andReturn([]);
        $this->app()->instance(TransferRepositoryInterface::class, $transferRepository);
        $this->assertSame(0, $this->runCommand('settlement:process-due-transfers', ['--date' => '2026-02-28']));
        $transfer = Mockery::mock(Transfer::class);
        $transfer->shouldReceive('transferIdentifier')->andReturn(new TransferIdentifier('00000000-0000-7000-8000-000000000001'));
        $transferRepository->shouldReceive('findDueTransfers')->once()->andReturn([$transfer]);
        $this->assertSame(0, $this->runCommand('settlement:process-due-transfers', ['--dry-run' => true]));
        Queue::assertNothingPushed();
    }

    public function testTransfersAreDispatchedToSettlementQueue(): void
    {
        Queue::fake();
        $transfer = Mockery::mock(Transfer::class);
        $transfer->shouldReceive('transferIdentifier')->andReturn(new TransferIdentifier('00000000-0000-7000-8000-000000000001'));
        $transferRepository = Mockery::mock(TransferRepositoryInterface::class);
        $transferRepository->shouldReceive('findDueTransfers')->once()->andReturn([$transfer, $transfer]);
        $this->app()->instance(TransferRepositoryInterface::class, $transferRepository);
        $this->assertSame(0, $this->runCommand('settlement:process-due-transfers', ['--date' => '2026-02-28']));
        Queue::assertPushedOn('settlement', ExecuteTransferJob::class);
        Queue::assertPushed(ExecuteTransferJob::class, 2);
    }

    public function testWikiCommandsDispatchExistingJobs(): void
    {
        Queue::fake();
        $this->assertSame(0, $this->runCommand('wiki:process-role-promotion', ['--month' => '2026-02']));
        Queue::assertPushed(ProcessRolePromotionJob::class, static fn (ProcessRolePromotionJob $job): bool => (string) $job->yearMonth === '2026-02');
        $this->assertSame(0, $this->runCommand('video-links:collect'));
        Queue::assertPushed(CollectVideoLinksJob::class);
    }

    public function testInvalidMonthFailsWithoutDispatch(): void
    {
        Queue::fake();
        $this->assertSame(1, $this->runCommand('wiki:process-role-promotion', ['--month' => '2026-13']));
        Queue::assertNothingPushed();
    }

    public function testSyncFailureIsNonzero(): void
    {
        $updateContributionPointSummary = Mockery::mock(UpdateContributionPointSummaryInterface::class);
        $updateContributionPointSummary->shouldReceive('process')->once()->andThrow(new RuntimeException('summary unavailable'));
        $this->app()->instance(UpdateContributionPointSummaryInterface::class, $updateContributionPointSummary);
        $this->assertSame(1, $this->runCommand('wiki:process-role-promotion', ['--month' => '2026-02', '--sync' => true]));
    }

    public function testSyncSuccessMeansBothUseCasesCompleted(): void
    {
        Queue::fake();
        $updateContributionPointSummary = Mockery::mock(UpdateContributionPointSummaryInterface::class);
        $updateContributionPointSummary->shouldReceive('process')->once()->andReturnNull();
        $processRolePromotion = Mockery::mock(ProcessRolePromotionInterface::class);
        $processRolePromotion->shouldReceive('process')->once()->andReturnNull();
        $this->app()->instance(UpdateContributionPointSummaryInterface::class, $updateContributionPointSummary);
        $this->app()->instance(ProcessRolePromotionInterface::class, $processRolePromotion);
        $this->assertSame(0, $this->runCommand('wiki:process-role-promotion', ['--month' => '2026-02', '--sync' => true]));
        Queue::assertNothingPushed();
    }

    #[DataProvider('enqueueCommands')]
    public function testEnqueueFailuresAreNonzero(string $command): void
    {
        Queue::shouldReceive('connection')->andThrow(new RuntimeException('SQS unavailable'));
        if ($command === 'settlement:process-due-transfers') {
            $transfer = Mockery::mock(Transfer::class);
            $transfer->shouldReceive('transferIdentifier')->andReturn(new TransferIdentifier('00000000-0000-7000-8000-000000000001'));
            $transferRepository = Mockery::mock(TransferRepositoryInterface::class);
            $transferRepository->shouldReceive('findDueTransfers')->once()->andReturn([$transfer]);
            $this->app()->instance(TransferRepositoryInterface::class, $transferRepository);
        }
        $this->assertSame(1, $this->runCommand($command));
    }

    /** @return array<string, array{string}> */
    public static function enqueueCommands(): array
    {
        return ['month' => ['wiki:process-role-promotion'], 'video' => ['video-links:collect'], 'transfer' => ['settlement:process-due-transfers']];
    }

    /** @param array<string, string|bool> $options */
    private function runCommand(string $command, array $options = []): int
    {
        return Artisan::call($command, $options, new BufferedOutput());
    }
}
