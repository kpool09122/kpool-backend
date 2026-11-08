<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Application\UseCase\Query\AppDailySummaryReportReadModel;
use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;
use Tests\Helper\AnalyzeAppMetrics;

class AppDailySummaryReportReadModelTest extends TestCase
{
    public function testReturnsAllNamedMetricResults(): void
    {
        $definitions = AnalyzeAppMetrics::definitions();
        $metrics = [];
        foreach ($definitions as $index => $definition) {
            $metrics[] = new MetricReportReadModel($definition, null, [], (string) $index, null);
        }
        $report = new AppDailySummaryReportReadModel(...$metrics);
        self::assertSame(['applicationUsers' => $metrics[0]->toArray(),'registeredUsers' => $metrics[1]->toArray()], $report->toArray());
        self::assertSame($metrics[0], $report->applicationUsers());
        self::assertSame($metrics[1], $report->registeredUsers());
    }
}
