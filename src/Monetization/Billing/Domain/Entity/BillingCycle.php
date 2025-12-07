<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\Entity;

use DateTimeImmutable;
use Source\Monetization\Billing\Domain\ValueObject\BillingCycleIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\BillingPeriod;

class BillingCycle
{
    public function __construct(
        private BillingCycleIdentifier $billingCycleIdentifier,
        private DateTimeImmutable  $anchorDate,
        private BillingPeriod      $billingPeriod,
        private ?DateTimeImmutable $nextBillingDate = null,
    ) {
    }

    public function billingCycleIdentifier(): BillingCycleIdentifier
    {
        return $this->billingCycleIdentifier;
    }

    public function anchorDate(): DateTimeImmutable
    {
        return $this->anchorDate;
    }

    public function billingPeriod(): BillingPeriod
    {
        return $this->billingPeriod;
    }

    public function nextBillingDate(): DateTimeImmutable
    {
        return $this->nextBillingDate ?? $this->anchorDate;
    }

    public function advance(): void
    {
        $this->nextBillingDate = $this->calculateNextDate($this->nextBillingDate());
    }

    public function isDue(DateTimeImmutable $currentDate): bool
    {
        return $currentDate >= $this->nextBillingDate();
    }

    private function calculateNextDate(DateTimeImmutable $from): DateTimeImmutable
    {
        return match ($this->billingPeriod) {
            BillingPeriod::MONTHLY => $from->modify('+1 month'),
            BillingPeriod::QUARTERLY => $from->modify('+3 months'),
            BillingPeriod::ANNUAL => $from->modify('+1 year'),
        };
    }
}
