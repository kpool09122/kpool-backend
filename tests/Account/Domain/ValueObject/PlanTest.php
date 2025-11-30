<?php

declare(strict_types=1);

namespace Tests\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\BillingCycle;
use Source\Account\Domain\ValueObject\Currency;
use Source\Account\Domain\ValueObject\Money;
use Source\Account\Domain\ValueObject\Plan;
use Source\Account\Domain\ValueObject\PlanDescription;
use Source\Account\Domain\ValueObject\PlanName;

class PlanTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $planName = new PlanName('Basic Plan');
        $billingCycle = BillingCycle::MONTHLY;
        $planDescription = new PlanDescription('');
        $money = new Money(10000, Currency::KRW);
        $plan = new Plan(
            $planName,
            $billingCycle,
            $planDescription,
            $money
        );

        $this->assertSame($planName, $plan->planName());
        $this->assertSame($billingCycle, $plan->billingCycle());
        $this->assertSame($planDescription, $plan->planDescription());
        $this->assertSame($money, $plan->money());
    }
}
