<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Repository;

use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\SettlementBatch;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementStatus;

interface SettlementBatchRepositoryInterface
{
    public function findById(SettlementBatchIdentifier $settlementBatchIdentifier): ?SettlementBatch;

    /**
     * @return array<SettlementBatch>
     */
    public function findByStatus(SettlementStatus $status): array;

    /**
     * @return array<SettlementBatch>
     */
    public function findByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): array;

    public function save(SettlementBatch $settlementBatch): void;
}
