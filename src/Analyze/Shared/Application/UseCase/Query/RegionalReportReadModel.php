<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Application\UseCase\Query;

/** @phpstan-import-type SerializedRegionalMetric from RegionalMetricReportReadModel */
readonly class RegionalReportReadModel
{
    /** @param array<string, RegionalMetricReportReadModel> $metrics */
    public function __construct(private array $metrics)
    {
    }

    /** @return array<string, SerializedRegionalMetric> */
    public function toArray(): array
    {
        return array_map(static fn (RegionalMetricReportReadModel $metric): array => $metric->toArray(), $this->metrics);
    }
}
