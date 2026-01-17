<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Repository;

use DateTimeImmutable;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;

interface TransferRepositoryInterface
{
    public function findById(TransferIdentifier $transferIdentifier): ?Transfer;

    public function findBySettlementBatchId(SettlementBatchIdentifier $settlementBatchIdentifier): ?Transfer;

    /**
     * @return array<Transfer>
     */
    public function findPendingTransfers(): array;

    /**
     * 送金日が到来したPending状態のTransferを取得する
     * 送金日 = SettlementBatch.period_end + SettlementSchedule.payout_delay_days
     *
     * @return array<Transfer>
     */
    public function findDueTransfers(DateTimeImmutable $currentDate): array;

    public function save(Transfer $transfer): void;
}
