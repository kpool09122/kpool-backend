<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Wiki\Application\UseCase\Query\WikiDailySummaryReportReadModel;
use Tests\Helper\AnalyzeWikiMetrics;

class WikiDailySummaryReportReadModelTest extends TestCase
{
    public function testEmptyReportDoesNotInventZero(): void
    {
        $definition = AnalyzeWikiMetrics::definition();
        $report = new WikiDailySummaryReportReadModel($definition, null, [], null, null);
        self::assertSame($definition, $report->definition());
        self::assertNull($report->revision());
        self::assertSame([], $report->days());
        self::assertNull($report->total());
        self::assertNull($report->denominator());
    }
}
