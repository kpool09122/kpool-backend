<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Application\Jobs\ExecuteTransferJob;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Source\Monetization\Settlement\Domain\Repository\TransferRepositoryInterface;

class ProcessDueTransfersCommand extends Command
{
    protected $signature = 'settlement:process-due-transfers
                            {--date= : 処理対象日（指定がなければ今日）}
                            {--dry-run : 実行せずに対象件数のみ表示}';

    protected $description = '送金日が到来したTransferの送金処理を実行する';

    public function handle(TransferRepositoryInterface $transferRepository): int
    {
        $dateString = $this->option('date');
        $currentDate = $dateString !== null
            ? new DateTimeImmutable($dateString)
            : new DateTimeImmutable('today');

        $this->info("Processing due transfers for: {$currentDate->format('Y-m-d')}");

        $dueTransfers = $transferRepository->findDueTransfers($currentDate);

        if ($dueTransfers === []) {
            $this->info('No due transfers found.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d due transfer(s).', count($dueTransfers)));

        if ($this->option('dry-run')) {
            $this->warn('Dry-run mode: No jobs dispatched.');
            foreach ($dueTransfers as $transfer) {
                $this->line("  - Transfer ID: {$transfer->transferIdentifier()}");
            }

            return self::SUCCESS;
        }

        foreach ($dueTransfers as $transfer) {
            ExecuteTransferJob::dispatch($transfer->transferIdentifier());
            $this->line("Dispatched job for Transfer ID: {$transfer->transferIdentifier()}");
        }

        $this->info('All jobs dispatched successfully.');

        return self::SUCCESS;
    }
}
