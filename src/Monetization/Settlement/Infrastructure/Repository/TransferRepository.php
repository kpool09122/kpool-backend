<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Infrastructure\Repository;

use Application\Models\Monetization\Transfer as TransferEloquent;
use DateTimeImmutable;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\Repository\TransferRepositoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\StripeTransferId;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferStatus;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;

class TransferRepository implements TransferRepositoryInterface
{
    public function findById(TransferIdentifier $transferIdentifier): ?Transfer
    {
        $eloquent = TransferEloquent::query()
            ->where('id', (string) $transferIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findBySettlementBatchId(SettlementBatchIdentifier $settlementBatchIdentifier): ?Transfer
    {
        $eloquent = TransferEloquent::query()
            ->where('settlement_batch_id', (string) $settlementBatchIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @return array<Transfer>
     */
    public function findPendingTransfers(): array
    {
        $eloquents = TransferEloquent::query()
            ->where('status', TransferStatus::PENDING->value)
            ->get();

        return $eloquents->map(fn (TransferEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    /**
     * @return array<Transfer>
     */
    public function findDueTransfers(DateTimeImmutable $currentDate): array
    {
        $eloquents = TransferEloquent::query()
            ->where('status', TransferStatus::PENDING->value)
            ->whereHas('settlementBatch.settlementSchedule', function ($query) use ($currentDate) {
                $query->whereRaw(
                    'settlement_batches.period_end + (settlement_schedules.payout_delay_days || \' days\')::interval <= ?::date',
                    [$currentDate->format('Y-m-d')]
                );
            })
            ->get();

        return $eloquents->map(fn (TransferEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function save(Transfer $transfer): void
    {
        TransferEloquent::query()->updateOrCreate(
            ['id' => (string) $transfer->transferIdentifier()],
            [
                'settlement_batch_id' => (string) $transfer->settlementBatchIdentifier(),
                'monetization_account_id' => (string) $transfer->monetizationAccountIdentifier(),
                'currency' => $transfer->amount()->currency()->value,
                'amount' => $transfer->amount()->amount(),
                'status' => $transfer->status()->value,
                'sent_at' => $transfer->sentAt(),
                'failed_at' => $transfer->failedAt(),
                'failure_reason' => $transfer->failureReason(),
                'stripe_transfer_id' => $transfer->stripeTransferId() !== null
                    ? (string) $transfer->stripeTransferId()
                    : null,
            ]
        );
    }

    private function toDomainEntity(TransferEloquent $eloquent): Transfer
    {
        return new Transfer(
            new TransferIdentifier($eloquent->id),
            new SettlementBatchIdentifier($eloquent->settlement_batch_id),
            new MonetizationAccountIdentifier($eloquent->monetization_account_id),
            new Money($eloquent->amount, Currency::from($eloquent->currency)),
            TransferStatus::from($eloquent->status),
            $eloquent->sent_at !== null ? new DateTimeImmutable($eloquent->sent_at->toDateTimeString()) : null,
            $eloquent->failed_at !== null ? new DateTimeImmutable($eloquent->failed_at->toDateTimeString()) : null,
            $eloquent->failure_reason,
            $eloquent->stripe_transfer_id !== null ? new StripeTransferId($eloquent->stripe_transfer_id) : null
        );
    }
}
