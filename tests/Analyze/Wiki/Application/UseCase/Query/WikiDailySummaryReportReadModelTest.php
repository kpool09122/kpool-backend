<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;
use Source\Analyze\Wiki\Application\UseCase\Query\WikiDailySummaryReportReadModel;
use Tests\Helper\AnalyzeWikiMetrics;

class WikiDailySummaryReportReadModelTest extends TestCase
{
    public function testReturnsAllNamedMetricResults(): void
    {
        $definitions = AnalyzeWikiMetrics::definitions();
        $metrics = [];
        foreach ($definitions as $index => $definition) {
            $metrics[] = new MetricReportReadModel($definition, null, [], (string) $index, null);
        }
        $report = new WikiDailySummaryReportReadModel(...$metrics);
        self::assertSame($metrics[0], $report->views());
        self::assertSame($metrics[1], $report->viewers());
        self::assertSame($metrics[2], $report->engagementTime());
        self::assertSame($metrics[3], $report->submissions());
        self::assertSame($metrics[4], $report->publications());
    }
}
