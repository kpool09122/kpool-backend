<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Domain\Entity;

use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Billing\Domain\Entity\BillingCycle;
use Source\Monetization\Billing\Domain\ValueObject\BillingCycleIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\BillingPeriod;
use Tests\Helper\StrTestHelper;

class BillingCycleTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成されること.
     *
     * @return void
     * @throws Exception
     */
    public function test__construct(): void
    {
        $billingCycleIdentifier = new BillingCycleIdentifier(StrTestHelper::generateUlid());
        $anchorDate = new DateTimeImmutable('now');
        $billingPeriod = BillingPeriod::ANNUAL;
        $nextBillingDate = new DateTimeImmutable('now')->modify('+ 1 month');
        $billingCycle = new BillingCycle($billingCycleIdentifier, $anchorDate, $billingPeriod, $nextBillingDate);
        $this->assertSame($billingCycleIdentifier, $billingCycle->billingCycleIdentifier());
        $this->assertSame($anchorDate, $billingCycle->anchorDate());
        $this->assertSame($billingPeriod, $billingCycle->billingPeriod());
        $this->assertSame($nextBillingDate, $billingCycle->nextBillingDate());
    }

    /**
     * 正常系: nextBillingDateがnullの場合は、anchorDateが返却されること.
     *
     * @return void
     */
    public function testNextBillingDateStartsFromAnchor(): void
    {
        $anchor = new DateTimeImmutable('2024-01-15');

        $cycle = new BillingCycle(
            new BillingCycleIdentifier(StrTestHelper::generateUlid()),
            $anchor,
            BillingPeriod::MONTHLY
        );

        $this->assertSame($anchor, $cycle->nextBillingDate());
    }

    /**
     * 正常系: advanceでnextBillingDateの日付がBillingPeriod分進められること.
     *
     * @return void
     * @throws Exception
     */
    public function testAdvanceMovesNextBillingDateByPeriod(): void
    {
        $billingCycleIdentifier = new BillingCycleIdentifier(StrTestHelper::generateUlid());
        $anchorDate = new DateTimeImmutable('2024-01-15');
        $cycle = new BillingCycle($billingCycleIdentifier, $anchorDate, BillingPeriod::MONTHLY);
        $cycle->advance();
        $this->assertSame($anchorDate, $cycle->anchorDate());
        $this->assertSame(
            $anchorDate->modify('+ 1 month')->format(DATE_ATOM),
            $cycle->nextBillingDate()->format(DATE_ATOM),
        );

        $cycle = new BillingCycle($billingCycleIdentifier, $anchorDate, BillingPeriod::QUARTERLY);
        $cycle->advance();
        $this->assertSame($anchorDate, $cycle->anchorDate());
        $this->assertSame(
            $anchorDate->modify('+ 3 month')->format(DATE_ATOM),
            $cycle->nextBillingDate()->format(DATE_ATOM),
        );

        $cycle = new BillingCycle($billingCycleIdentifier, $anchorDate, BillingPeriod::ANNUAL);
        $cycle->advance();
        $this->assertSame($anchorDate, $cycle->anchorDate());
        $this->assertSame(
            $anchorDate->modify('+ 1 year')->format(DATE_ATOM),
            $cycle->nextBillingDate()->format(DATE_ATOM),
        );
    }

    /**
     * 正常系: 現在の日付が請求日より前かどうか判定できること.
     *
     * @return void
     */
    public function testIsDue(): void
    {
        $cycle = new BillingCycle(
            new BillingCycleIdentifier(StrTestHelper::generateUlid()),
            new DateTimeImmutable('2024-01-15'),
            BillingPeriod::ANNUAL
        );

        $this->assertFalse($cycle->isDue(new DateTimeImmutable('2024-01-14')));
        $this->assertTrue($cycle->isDue(new DateTimeImmutable('2024-01-15')));
    }
}
