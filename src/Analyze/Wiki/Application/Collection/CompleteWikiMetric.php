<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\Collection;

use Source\Analyze\Wiki\Application\Service\DailyMetricsFetchResult;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Domain\ValueObject\MetricObservation;

/** A fully fetched report: absent targets mean measured zero, never unavailable data. */
readonly class CompleteWikiMetric
{
    /** @var array<string, MetricObservation> */
    private array $observations;

    public function __construct(DailyMetricsFetchResult $result, private bool $engagement = false)
    {
        $observations = [];
        foreach ($result->orderedObservations() as $observation) {
            $observations[$observation->targetKey()] = $observation;
        }
        $this->observations = $observations;
    }

    public function forTarget(MetricObservation $target): MetricObservation
    {
        return $this->observations[$target->targetKey()] ?? new MetricObservation(
            $target->targetIdentifier,
            $target->translationSetIdentifier,
            $target->language,
            $target->country,
            '0',
            $this->engagement ? 0 : null,
        );
    }

    /** @return list<MetricObservation> */
    public static function targets(self ...$metrics): array
    {
        $targets = [];
        foreach ($metrics as $metric) {
            foreach ($metric->observations as $key => $observation) {
                if (isset($targets[$key]) && ! $targets[$key]->hasSameTargetAs($observation)) {
                    throw new InvalidDailySummaryException('Wiki metrics must agree on translation set.');
                }
                $targets[$key] = $observation;
            }
        }
        ksort($targets);

        return array_values($targets);
    }
}
