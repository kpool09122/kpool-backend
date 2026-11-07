<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Application\UseCase\Query;

use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;

/**
 * @phpstan-type SerializedMetricReport array{definition: array{source: string, metric: string, grain: string, version: string}, revision: ?string, days: list<DailyResult>, total: ?string, denominator: ?int, actorCount: ?string, countries: list<CountryResult>}
 * @phpstan-type CountryResult array{country: ?string, value: ?string, denominator: ?int, quality: list<string>, actorCount?: ?string}
 * @phpstan-type DailyResult array{day: string, availability: string, value: ?string, denominator: ?int, quality: list<string>, updatedAt: ?string, actorCount?: ?string}
 */
readonly class MetricReportReadModel
{
    /**
     * @param list<CountryResult> $countries
     * @param list<DailyResult> $days
     * @param ?string $total Period total, or the final closing balance for a snapshot metric.
     */
    public function __construct(
        private MetricDefinition $definition,
        private ?string $revision,
        private array $days,
        private ?string $total,
        private ?int $denominator,
        private ?string $actorCount = null,
        private array $countries = [],
    ) {
    }

    /** @return SerializedMetricReport */
    public function toArray(): array
    {
        return [
            'definition' => ['source' => $this->definition->source, 'metric' => $this->definition->metric->value, 'grain' => $this->definition->grain, 'version' => $this->definition->version],
            'revision' => $this->revision, 'days' => $this->days, 'total' => $this->total, 'denominator' => $this->denominator, 'actorCount' => $this->actorCount, 'countries' => $this->countries,
        ];
    }

    /** @return list<CountryResult> */
    public function countries(): array
    {
        return $this->countries;
    }

    /** Distinct operation Principals over the whole selected scope, never a sum of daily actors. */
    public function actorCount(): ?string
    {
        return $this->actorCount;
    }

    public function definition(): MetricDefinition
    {
        return $this->definition;
    }

    public function revision(): ?string
    {
        return $this->revision;
    }

    /** @return list<DailyResult> */
    public function days(): array
    {
        return $this->days;
    }

    public function total(): ?string
    {
        return $this->total;
    }

    public function denominator(): ?int
    {
        return $this->denominator;
    }
}
