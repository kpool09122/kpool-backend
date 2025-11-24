<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

readonly class Plan
{
    public function __construct(
        private PlanName $planName,
        private BillingCycle $billingCycle,
        private PlanDescription $planDescription,
        private Money $money,
    ) {
    }

    public function planName(): PlanName
    {
        return $this->planName;
    }

    public function billingCycle(): BillingCycle
    {
        return $this->billingCycle;
    }

    public function planDescription(): PlanDescription
    {
        return $this->planDescription;
    }

    public function money(): Money
    {
        return $this->money;
    }
}
