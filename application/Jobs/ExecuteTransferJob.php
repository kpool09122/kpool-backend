<?php

declare(strict_types=1);

namespace Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Source\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer\ExecuteTransferInput;
use Source\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer\ExecuteTransferInterface;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Throwable;

class ExecuteTransferJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly TransferIdentifier $transferIdentifier
    ) {
        $this->onQueue('settlement');
    }

    public function handle(ExecuteTransferInterface $executeTransfer): void
    {
        Log::info('ExecuteTransferJob started', [
            'transfer_id' => (string) $this->transferIdentifier,
        ]);

        $input = new ExecuteTransferInput($this->transferIdentifier);
        $executeTransfer->process($input);

        Log::info('ExecuteTransferJob completed', [
            'transfer_id' => (string) $this->transferIdentifier,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ExecuteTransferJob failed permanently', [
            'transfer_id' => (string) $this->transferIdentifier,
            'exception' => $exception->getMessage(),
        ]);
    }
}
