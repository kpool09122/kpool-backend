<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPreviousPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

class ReportingPreviousPeriodTest extends TestCase
{
    public function testPrecedingSingleDayEndsImmediatelyBeforeSelectedPeriod(): void
    {
        $period = new ReportingPeriod(new UtcDate('2020-03-01'), new UtcDate('2020-03-01'), new DateTimeImmutable('2026-11-10T00:00:00Z'));
        $previous = new ReportingPreviousPeriod($period);
        self::assertSame('2020-02-29', (string) $previous->start());
        self::assertSame('2020-02-29', (string) $previous->endInclusive());
        self::assertSame((string) $period->start(), (string) $previous->endExclusive());
        self::assertCount(1, iterator_to_array($previous->days()));
    }

    public function testPreviousPeriodContainsTheSameNumberOfDaysAcrossTheYearBoundary(): void
    {
        $period = new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-03'), new DateTimeImmutable('2026-11-10T00:00:00Z'));
        $previous = new ReportingPreviousPeriod($period);
        self::assertSame(['2019-12-29', '2019-12-30', '2019-12-31'], array_map(static fn (UtcDate $day): string => (string) $day, iterator_to_array($previous->days())));
    }

    public function testEqualDayPreviousPeriodCanExceedSixCalendarMonths(): void
    {
        $period = new ReportingPeriod(new UtcDate('2024-03-01'), new UtcDate('2024-08-31'), new DateTimeImmutable('2026-11-10T00:00:00Z'));
        $previous = new ReportingPreviousPeriod($period);
        self::assertSame('2023-08-30', (string) $previous->start());
        self::assertSame('2024-02-29', (string) $previous->endInclusive());
        self::assertCount(184, iterator_to_array($previous->days()));
        self::assertSame((string) $period->start(), (string) $previous->endExclusive());
    }
}
