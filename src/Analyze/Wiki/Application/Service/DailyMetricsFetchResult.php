<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\Service;

use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Domain\ValueObject\MetricObservation;

readonly class DailyMetricsFetchResult
{
    /**
     * @param list<MetricObservation> $observations
     * @param list<string> $quality
     */
    public function __construct(private array $observations, private array $quality)
    {
    }

    /** @return list<MetricObservation> */
    public function observations(): array
    {
        return $this->observations;
    }

    /** @return list<string> */
    public function quality(): array
    {
        return $this->quality;
    }

    /**
     * @return list<MetricObservation>
     * @throws InvalidDailySummaryException
     */
    public function orderedObservations(): array
    {
        $observations = $this->observations;
        usort($observations, static fn (MetricObservation $left, MetricObservation $right): int => strcmp($left->targetKey(), $right->targetKey()));
        $previous = null;
        foreach ($observations as $observation) {
            if ($previous === $observation->targetKey()) {
                throw new InvalidDailySummaryException('Duplicate Wiki metric target.');
            }
            $previous = $observation->targetKey();
        }

        return $observations;
    }
}
