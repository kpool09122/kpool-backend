<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInput;
use Tests\Helper\AnalyzeWikiMetrics;

class GetWikiDailySummaryReportInputTest extends TestCase
{
    public function testInputPreservesObjectsAndFilters(): void
    {
        $period = new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now());
        $input = new GetWikiDailySummaryReportInput($period, 'wiki', 'set', 'ja', 'JP');
        self::assertSame($period, $input->period());
        self::assertSame('wiki', $input->wikiIdentifier());
        self::assertSame('set', $input->translationSetIdentifier());
        self::assertSame('ja', $input->language());
        self::assertSame('JP', $input->country());
        self::assertSame('JP', (new GetWikiDailySummaryReportInput($period, country: 'JP'))->country());
    }
}
