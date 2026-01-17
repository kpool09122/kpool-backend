<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Repository;

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

    public function save(Transfer $transfer): void;
}
