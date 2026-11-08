<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport;

use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInput;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Tests\Helper\AnalyzeAppMetrics;

class GetAppDailySummaryReportInputTest extends TestCase
{
    public function testInputPreservesObjectsAndFilters(): void
    {
        $principalIdentifier = new PrincipalIdentifier('00000000-0000-7000-8000-000000000001');
        $period = new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-01'), AnalyzeAppMetrics::now());
        $input = new GetAppDailySummaryReportInput($principalIdentifier, $period);
        self::assertSame($principalIdentifier, $input->principalIdentifier());
        self::assertSame($period, $input->period());
    }
}
