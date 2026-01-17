<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementInterval;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementScheduleIdentifier;
use Source\Shared\Domain\ValueObject\Money;

class SettlementSchedule
{
    private DateTimeImmutable $nextClosingDate;

    public function __construct(
        private SettlementScheduleIdentifier $settlementScheduleIdentifier,
        private MonetizationAccountIdentifier $monetizationAccountIdentifier,
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

    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier
    {
        return $this->monetizationAccountIdentifier;
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

    /**
     * 次の締め日を計算する。
     *
     * 注意: MONTHLYの場合、PHPの +1 month は月末日（29日、30日、31日）で予期しない結果になる。
     * 例: 1月31日 + 1 month = 3月2日（2月31日は存在しないため）
     * そのため、締め日には全ての月に存在する日付（1日〜28日、例: 25日締め、10日締め）を使用すること。
     */
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
