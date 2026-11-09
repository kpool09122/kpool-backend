<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Source\Analyze\App\Application\Service\AppDailySummaryData;
use Source\Analyze\App\Domain\ValueObject\AppCountryMetrics;
use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

class AnalyzeAppMetrics
{
    public static function definition(Metric $metric = Metric::APPLICATION_USERS, ?string $source = null): MetricDefinition
    {
        return new MetricDefinition($source ?? ($metric === Metric::REGISTERED_USERS ? 'identity_history' : 'ga4'), $metric, 'application_day', '1');
    }

    /** @return list<MetricDefinition> */
    public static function definitions(): array
    {
        return array_map(static fn (Metric $metric): MetricDefinition => self::definition($metric), [Metric::APPLICATION_USERS, Metric::REGISTERED_USERS]);
    }

    /**
     * @param array<string, string> $values
     * @return array{ApplicationUserCount, RegisteredUserSnapshot}
     */
    public static function analysisValues(UtcDate $day, array $values = []): array
    {
        $origin = static fn (Metric $metric): MetricProvenance => new MetricProvenance(self::definition($metric), $day, self::now());

        return [
            new ApplicationUserCount($values[Metric::APPLICATION_USERS->value] ?? '5', $origin(Metric::APPLICATION_USERS)),
            new RegisteredUserSnapshot($values[Metric::REGISTERED_USERS->value] ?? '5', $day->next()->instant(), $origin(Metric::REGISTERED_USERS)),
        ];
    }

    /** @param array<string, string> $values
     * @return array{ApplicationUserCount, RegisteredUserSnapshot, list<AppCountryMetrics>}
     */
    public static function summaryValues(UtcDate $day, array $values = []): array
    {
        [$users, $registered] = self::analysisValues($day, $values);

        return [$users, $registered, [new AppCountryMetrics(null, $users, $registered)]];
    }

    /** @param array<string, string> $values */
    public static function data(UtcDate $day, array $values = []): AppDailySummaryData
    {
        return new AppDailySummaryData(...self::summaryValues($day, $values));
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-02T00:00:00Z');
    }
}
