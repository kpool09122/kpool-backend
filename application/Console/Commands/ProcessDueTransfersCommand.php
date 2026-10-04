<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Application\Jobs\ExecuteTransferJob;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command;
use Override;
use Source\Monetization\Settlement\Domain\Repository\TransferRepositoryInterface;
use Throwable;

class ProcessDueTransfersCommand extends Command
{
    #[Override]
    protected $signature = 'settlement:process-due-transfers
                            {--date= : 処理対象日（YYYY-MM-DD形式、Asia/Tokyo、指定がなければ今日）}
                            {--dry-run : 実行せずに対象件数のみ表示}';

    #[Override]
    protected $description = '送金日が到来したTransferの送金処理を実行する';

    public function handle(TransferRepositoryInterface $transferRepository): int
    {
        $dateString = $this->option('date');
        $timezone = new DateTimeZone('Asia/Tokyo');
        $currentDate = $dateString !== null
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $dateString, $timezone)
            : new DateTimeImmutable('today', $timezone);

        if ($currentDate === false || ($dateString !== null && $currentDate->format('Y-m-d') !== $dateString)) {
            $this->error('Invalid date. Please use YYYY-MM-DD format.');

            return self::FAILURE;
        }

        try {
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
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Transfer command failed. See application logs.');

            return self::FAILURE;
        }
    }
}
