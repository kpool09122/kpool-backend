<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Application\UseCase\Query;

/**
 * @phpstan-import-type CountryResult from MetricReportReadModel
 * @phpstan-import-type SerializedMetricReport from MetricReportReadModel
 * @phpstan-type RegionResult array{region: ?string, mapSupported: bool, value: ?string, denominator: ?int, quality: list<string>, actorCount?: ?string}
 * @phpstan-type SerializedRegionalMetric array{definition: array{source: string, metric: string, grain: string, version: string}, revision: ?string, days: list<array{day: string, availability: string, value: ?string, denominator: ?int, quality: list<string>, updatedAt: ?string, actorCount?: ?string}>, total: ?string, denominator: ?int, actorCount: ?string, regions: list<RegionResult>}
 */
readonly class RegionalMetricReportReadModel
{
    /** @param list<RegionResult> $regions */
    public function __construct(private MetricReportReadModel $selection, private array $regions)
    {
    }

    /** @return SerializedRegionalMetric */
    public function toArray(): array
    {
        $data = $this->selection->toArray();
        unset($data['countries']);

        return [...$data, 'regions' => $this->regions];
    }
}
