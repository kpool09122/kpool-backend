<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Infrastructure\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Shared\Infrastructure\Service\UtcDailyReportTimeZone;

class UtcDailyReportTimeZoneTest extends TestCase
{
    #[DataProvider('timeZones')]
    public function testMatchesOnlyDaysWhoseEntireReportingIntervalIsUtc(string $timeZone, string $day, bool $expected): void
    {
        self::assertSame($expected, UtcDailyReportTimeZone::matches($timeZone, new UtcDate($day)));
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function timeZones(): iterable
    {
        foreach (['UTC', 'Etc/UTC', 'Etc/GMT', 'GMT', 'Atlantic/Reykjavik', 'Africa/Abidjan', '+00:00'] as $zone) {
            yield $zone => [$zone, '2026-11-05', true];
        }
        yield 'nonzero fixed offset' => ['+09:00', '2026-11-05', false];
        yield 'Tokyo' => ['Asia/Tokyo', '2026-11-05', false];
        yield 'invalid zone' => ['Invalid/TimeZone', '2026-11-05', false];
        yield 'empty zone' => ['', '2026-11-05', false];
        yield 'London winter' => ['Europe/London', '2026-01-01', true];
        yield 'London summer' => ['Europe/London', '2026-07-01', false];
        yield 'DST starts after UTC midnight' => ['Europe/London', '2026-03-29', false];
        yield 'DST ends after UTC midnight' => ['Europe/London', '2026-10-25', false];
        yield 'historical Reykjavik offset' => ['Atlantic/Reykjavik', '1967-01-01', false];
    }
}
