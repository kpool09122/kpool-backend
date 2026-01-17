<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Infrastructure\Repository;

use Application\Models\Monetization\SettlementBatch as SettlementBatchEloquent;
use DateTimeImmutable;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\SettlementBatch;
use Source\Monetization\Settlement\Domain\Repository\SettlementBatchRepositoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementStatus;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;

readonly class SettlementBatchRepository implements SettlementBatchRepositoryInterface
{
    public function findById(SettlementBatchIdentifier $settlementBatchIdentifier): ?SettlementBatch
    {
        $eloquent = SettlementBatchEloquent::query()
            ->where('id', (string) $settlementBatchIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @return array<SettlementBatch>
     */
    public function findByStatus(SettlementStatus $status): array
    {
        $eloquents = SettlementBatchEloquent::query()
            ->where('status', $status->value)
            ->get();

        return $eloquents->map(fn (SettlementBatchEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    /**
     * @return array<SettlementBatch>
     */
    public function findByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): array
    {
        $eloquents = SettlementBatchEloquent::query()
            ->where('monetization_account_id', (string) $monetizationAccountIdentifier)
            ->get();

        return $eloquents->map(fn (SettlementBatchEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function save(SettlementBatch $settlementBatch): void
    {
        SettlementBatchEloquent::query()->updateOrCreate(
            ['id' => (string) $settlementBatch->settlementBatchIdentifier()],
            [
                'monetization_account_id' => (string) $settlementBatch->monetizationAccountIdentifier(),
                'currency' => $settlementBatch->currency()->value,
                'gross_amount' => $settlementBatch->grossAmount()->amount(),
                'fee_amount' => $settlementBatch->feeAmount()->amount(),
                'net_amount' => $settlementBatch->netAmount()->amount(),
                'period_start' => $settlementBatch->periodStart(),
                'period_end' => $settlementBatch->periodEnd(),
                'status' => $settlementBatch->status()->value,
                'processed_at' => $settlementBatch->processedAt(),
                'paid_at' => $settlementBatch->paidAt(),
                'failed_at' => $settlementBatch->failedAt(),
                'failure_reason' => $settlementBatch->failureReason(),
            ]
        );
    }

    private function toDomainEntity(SettlementBatchEloquent $eloquent): SettlementBatch
    {
        return new SettlementBatch(
            new SettlementBatchIdentifier($eloquent->id),
            new MonetizationAccountIdentifier($eloquent->monetization_account_id),
            Currency::from($eloquent->currency),
            new DateTimeImmutable($eloquent->period_start->toDateString()),
            new DateTimeImmutable($eloquent->period_end->toDateString()),
            SettlementStatus::from($eloquent->status),
            new Money($eloquent->gross_amount, Currency::from($eloquent->currency)),
            new Money($eloquent->fee_amount, Currency::from($eloquent->currency)),
            $eloquent->processed_at !== null ? new DateTimeImmutable($eloquent->processed_at->toDateTimeString()) : null,
            $eloquent->paid_at !== null ? new DateTimeImmutable($eloquent->paid_at->toDateTimeString()) : null,
            $eloquent->failed_at !== null ? new DateTimeImmutable($eloquent->failed_at->toDateTimeString()) : null,
            $eloquent->failure_reason,
        );
    }
}
