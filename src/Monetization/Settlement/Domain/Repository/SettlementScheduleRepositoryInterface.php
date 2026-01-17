<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Repository;

use DateTimeImmutable;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\SettlementSchedule;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementScheduleIdentifier;

interface SettlementScheduleRepositoryInterface
{
    public function findById(SettlementScheduleIdentifier $settlementScheduleIdentifier): ?SettlementSchedule;

    public function findByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): ?SettlementSchedule;

    /**
     * @return array<SettlementSchedule>
     */
    public function findDueSchedules(DateTimeImmutable $date): array;

    public function save(SettlementSchedule $settlementSchedule): void;
}
