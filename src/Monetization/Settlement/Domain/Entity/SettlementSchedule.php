<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementInterval;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementScheduleIdentifier;
use Source\Shared\Domain\ValueObject\Money;

class SettlementSchedule
{
    private DateTimeImmutable $nextClosingDate;

    public function __construct(
        private SettlementScheduleIdentifier $settlementScheduleIdentifier,
        DateTimeImmutable $anchorDate,
        private SettlementInterval $interval,
        private int $payoutDelayDays = 0,
        private ?Money $threshold = null
    ) {
        $this->assertPayoutDelayDays($payoutDelayDays);
        $this->assertThreshold();
        $this->nextClosingDate = $anchorDate;
    }

    public function settlementScheduleIdentifier(): SettlementScheduleIdentifier
    {
        return $this->settlementScheduleIdentifier;
    }

    public function interval(): SettlementInterval
    {
        return $this->interval;
    }

    public function paymentDelayDays(): int
    {
        return $this->payoutDelayDays;
    }

    public function threshold(): ?Money
    {
        return $this->threshold;
    }

    public function nextClosingDate(): DateTimeImmutable
    {
        return $this->nextClosingDate;
    }

    public function nextPayoutDate(): DateTimeImmutable
    {
        return $this->nextClosingDate()->modify(sprintf('+%d days', $this->payoutDelayDays));
    }

    public function isDue(DateTimeImmutable $currentDate, ?Money $availableBalance = null): bool
    {
        if ($this->interval === SettlementInterval::THRESHOLD) {
            if ($availableBalance === null) {
                throw new DomainException('Available balance is required for threshold-based schedule.');
            }
            $this->assertThresholdCurrency($availableBalance);

            return $availableBalance->amount() >= $this->threshold->amount();
        }

        return $currentDate >= $this->nextPayoutDate();
    }

    public function advance(): void
    {
        if ($this->interval === SettlementInterval::THRESHOLD) {
            return;
        }

        $this->nextClosingDate = $this->calculateNextClosingDate($this->nextClosingDate);
    }

    private function calculateNextClosingDate(DateTimeImmutable $date): DateTimeImmutable
    {
        return match ($this->interval) {
            SettlementInterval::MONTHLY => $date->modify('+1 month'),
            SettlementInterval::BIWEEKLY => $date->modify('+2 weeks'),
            SettlementInterval::THRESHOLD => $date,
        };
    }

    private function assertPayoutDelayDays(int $payoutDelayDays): void
    {
        if ($payoutDelayDays < 0) {
            throw new InvalidArgumentException('Payout delay days must be zero or positive.');
        }
        if ($this->interval === SettlementInterval::THRESHOLD && $payoutDelayDays > 0) {
            throw new InvalidArgumentException('Payout delay days must be zero for threshold-based schedule.');
        }
    }

    private function assertThreshold(): void
    {
        if ($this->interval === SettlementInterval::THRESHOLD && $this->threshold === null) {
            throw new InvalidArgumentException('Threshold amount is required for threshold-based schedule.');
        }
    }

    private function assertThresholdCurrency(Money $availableBalance): void
    {
        if ($this->threshold === null) {
            throw new DomainException('Threshold is not configured.');
        }
        if (! $availableBalance->isSameCurrency($this->threshold)) {
            throw new DomainException('Available balance currency does not match threshold currency.');
        }
    }
}
