<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\BillingCycle;
use Source\Account\Account\Domain\ValueObject\Plan;
use Source\Account\Account\Domain\ValueObject\PlanDescription;
use Source\Account\Account\Domain\ValueObject\PlanName;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;

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
