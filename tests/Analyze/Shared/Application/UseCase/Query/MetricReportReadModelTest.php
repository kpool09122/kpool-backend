<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;
use Tests\Helper\AnalyzeWikiMetrics;

class MetricReportReadModelTest extends TestCase
{
    public function testPeriodActorCountIsSeparateFromAdditiveOperationCount(): void
    {
        $report = new MetricReportReadModel(AnalyzeWikiMetrics::definition(), 'revision', [], '8', null, '2');
        self::assertSame('8', $report->total());
        self::assertSame('2', $report->actorCount());
    }

    public function testSerializationPreservesNullableMetricContract(): void
    {
        $definition = AnalyzeWikiMetrics::definition();
        $report = new MetricReportReadModel($definition, null, [], null, null);
        self::assertSame([
            'definition' => ['source' => $definition->source, 'metric' => $definition->metric->value, 'grain' => $definition->grain, 'version' => $definition->version],
            'revision' => null, 'days' => [], 'total' => null, 'denominator' => null, 'actorCount' => null, 'countries' => [], 'comparison' => null,
        ], $report->toArray());
    }

    public function testEmptyReportDoesNotInventZero(): void
    {
        $definition = AnalyzeWikiMetrics::definition();
        $report = new MetricReportReadModel($definition, null, [], null, null);
        self::assertSame($definition, $report->definition());
        self::assertNull($report->revision());
        self::assertSame([], $report->days());
        self::assertNull($report->total());
        self::assertNull($report->denominator());
        self::assertNull($report->actorCount());
    }
}
