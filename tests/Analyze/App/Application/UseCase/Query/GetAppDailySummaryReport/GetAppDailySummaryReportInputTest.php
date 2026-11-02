<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport;

use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInput;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Tests\Helper\AnalyzeAppMetrics;

class GetAppDailySummaryReportInputTest extends TestCase
{
    public function testInputPreservesObjectsAndFilters(): void
    {
        $definition = AnalyzeAppMetrics::definition();
        $period = new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-01'), AnalyzeAppMetrics::now());
        $input = new GetAppDailySummaryReportInput($definition, $period);
        self::assertSame($definition, $input->definition());
        self::assertSame($period, $input->period());
    }
}
