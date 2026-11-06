<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Application\UseCase\Query\AppDailySummaryReportReadModel;
use Tests\Helper\AnalyzeAppMetrics;

class AppDailySummaryReportReadModelTest extends TestCase
{
    public function testEmptyReportDoesNotInventZero(): void
    {
        $definition = AnalyzeAppMetrics::definition();
        $report = new AppDailySummaryReportReadModel($definition, null, [], null);
        self::assertSame($definition, $report->definition());
        self::assertNull($report->revision());
        self::assertSame([], $report->days());
        self::assertNull($report->total());
    }
}
