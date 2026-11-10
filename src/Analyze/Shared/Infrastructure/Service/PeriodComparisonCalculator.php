<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Infrastructure\Service;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Source\Analyze\Shared\Application\Service\PeriodComparisonCalculatorInterface;
use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;
use Source\Analyze\Shared\Application\UseCase\Query\PeriodComparisonReadModel;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPreviousPeriod;

/** @phpstan-import-type ComparisonReason from PeriodComparisonReadModel */
readonly class PeriodComparisonCalculator implements PeriodComparisonCalculatorInterface
{
    public function calculate(MetricReportReadModel $current, MetricReportReadModel $previous, ReportingPreviousPeriod $previousPeriod): PeriodComparisonReadModel
    {
        [$percentage, $reason] = self::compare($current, $previous, false);
        [$actorPercentage, $actorReason] = self::compare($current, $previous, true);

        return new PeriodComparisonReadModel(
            from: (string) $previousPeriod->start(),
            to: (string) $previousPeriod->endInclusive(),
            percentageChange: $percentage,
            reason: $reason,
            actorPercentageChange: $actorPercentage,
            actorReason: $actorReason,
        );
    }

    /** @return array{?string, ?ComparisonReason} */
    private static function compare(MetricReportReadModel $current, MetricReportReadModel $previous, bool $actors): array
    {
        $metric = $current->definition()->metric;
        if ($actors && ! in_array($metric, [Metric::SUBMISSIONS, Metric::PUBLICATIONS], true)) {
            return [null, 'not_applicable'];
        }
        // Readers aggregate the available measurements. A missing/partial day does not
        // invalidate the known period total or its period-distinct actor count.
        $previousValue = $actors ? $previous->actorCount() : $previous->total();
        if ($previousValue === null) {
            return [null, 'no_previous_data'];
        }
        $values = [];
        foreach ([$current, $previous] as $index => $report) {
            $value = $actors ? $report->actorCount() : $report->total();
            if ($value === null || ! preg_match('/^\d+(?:\.\d+)?$/D', $value)) {
                return [null, $index === 1 ? 'no_previous_data' : 'no_current_data'];
            }
            $number = BigRational::of($value);
            if (! $actors && $metric === Metric::ENGAGEMENT_SECONDS) {
                $denominator = $report->denominator();
                if ($denominator === null || $denominator <= 0) {
                    return [null, 'invalid_denominator'];
                }
                $number = $number->dividedBy($denominator);
            }
            $values[] = $number;
        }
        if ($values[1]->isZero()) {
            return [null, 'zero_baseline'];
        }
        $change = $values[0]->minus($values[1])->dividedBy($values[1])->multipliedBy(100)->toScale(1, RoundingMode::HalfUp);

        return [$change->isZero() ? '0.0' : (string) $change, null];
    }
}
