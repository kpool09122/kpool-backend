<?php

declare(strict_types=1);

namespace Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount\SyncPayoutAccountInput;
use Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount\SyncPayoutAccountInterface;
use Source\Monetization\Account\Domain\ValueObject\AccountHolderType;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;
use Throwable;

class SyncPayoutAccountJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly string $connectedAccountId,
        private readonly string $externalAccountId,
        private readonly string $eventType,
        private readonly ?string $bankName = null,
        private readonly ?string $last4 = null,
        private readonly ?string $country = null,
        private readonly ?string $currency = null,
        private readonly ?string $accountHolderType = null,
        private readonly bool $isDefault = false,
    ) {
        $this->onQueue('webhook');
    }

    public function handle(SyncPayoutAccountInterface $syncPayoutAccount): void
    {
        Log::info('SyncPayoutAccountJob started', [
            'connected_account_id' => $this->connectedAccountId,
            'external_account_id' => $this->externalAccountId,
            'event_type' => $this->eventType,
        ]);

        $input = new SyncPayoutAccountInput(
            connectedAccountId: new ConnectedAccountId($this->connectedAccountId),
            externalAccountId: new ExternalAccountId($this->externalAccountId),
            eventType: $this->eventType,
            bankName: $this->bankName,
            last4: $this->last4,
            country: $this->country,
            currency: $this->currency,
            accountHolderType: $this->accountHolderType !== null
                ? AccountHolderType::from($this->accountHolderType)
                : null,
            isDefault: $this->isDefault,
        );

        DB::transaction(static function () use ($syncPayoutAccount, $input): void {
            $syncPayoutAccount->process($input);
        });

        Log::info('SyncPayoutAccountJob completed', [
            'connected_account_id' => $this->connectedAccountId,
            'external_account_id' => $this->externalAccountId,
            'event_type' => $this->eventType,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SyncPayoutAccountJob failed permanently', [
            'connected_account_id' => $this->connectedAccountId,
            'external_account_id' => $this->externalAccountId,
            'event_type' => $this->eventType,
            'exception' => $exception->getMessage(),
        ]);
    }
}
