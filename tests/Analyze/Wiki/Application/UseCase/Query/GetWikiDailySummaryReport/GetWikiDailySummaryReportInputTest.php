<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInput;
use Tests\Helper\AnalyzeWikiMetrics;

class GetWikiDailySummaryReportInputTest extends TestCase
{
    public function testInputPreservesObjectsAndFilters(): void
    {
        $definition = AnalyzeWikiMetrics::definition();
        $period = new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now());
        $input = new GetWikiDailySummaryReportInput($definition, $period, 'wiki', 'set', 'ja', 'JP');
        self::assertSame($definition, $input->definition());
        self::assertSame($period, $input->period());
        self::assertSame('wiki', $input->wikiIdentifier());
        self::assertSame('set', $input->translationSetIdentifier());
        self::assertSame('ja', $input->language());
        self::assertSame('JP', $input->country());
        self::assertSame('JP', (new GetWikiDailySummaryReportInput(AnalyzeWikiMetrics::definition(Metric::VIEWERS), $period, country: 'JP'))->country());
    }
}
