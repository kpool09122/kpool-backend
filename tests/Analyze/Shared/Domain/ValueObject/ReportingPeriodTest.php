<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

class ReportingPeriodTest extends TestCase
{
    public function testCalendarYearUsesClampedAnniversaryAndHalfOpenEnd(): void
    {
        $period = new ReportingPeriod(new UtcDate('2020-02-29'), new UtcDate('2021-02-27'), new DateTimeImmutable('2026-11-02T00:00:00Z'));
        self::assertSame('2021-02-28', (string) $period->endExclusive());
        self::assertCount(365, iterator_to_array($period->days()));
        $this->expectException(InvalidArgumentException::class);
        new ReportingPeriod(new UtcDate('2020-02-29'), new UtcDate('2021-02-28'), new DateTimeImmutable('2026-11-02T00:00:00Z'));
    }

    public function testDMinusThreeIsAllowedButDMinusTwoIsNot(): void
    {
        $now = new DateTimeImmutable('2026-11-02T23:59:59Z');
        $period = new ReportingPeriod(new UtcDate('2026-10-30'), new UtcDate('2026-10-30'), $now);
        self::assertSame('2026-10-31', (string) $period->endExclusive());
    }

    public function testLeapYearCanContain366DaysAndTimezoneUsesUtc(): void
    {
        $period = new ReportingPeriod(new UtcDate('2019-03-01'), new UtcDate('2020-02-29'), new DateTimeImmutable('2026-11-03T08:59:59+09:00'));
        self::assertCount(366, iterator_to_array($period->days()));
        self::assertSame('2020-03-01', (string) $period->endExclusive());
        $this->expectException(InvalidArgumentException::class);
        new ReportingPeriod(new UtcDate('2026-10-31'), new UtcDate('2026-10-31'), new DateTimeImmutable('2026-11-03T08:59:59+09:00'));
    }

    public function testReversedPeriodIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ReportingPeriod(new UtcDate('2020-01-02'), new UtcDate('2020-01-01'), new DateTimeImmutable('2026-11-02T00:00:00Z'));
    }

    public function testRecentDateIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ReportingPeriod(new UtcDate('2026-10-31'), new UtcDate('2026-10-31'), new DateTimeImmutable('2026-11-02T00:00:00Z'));
    }
}
