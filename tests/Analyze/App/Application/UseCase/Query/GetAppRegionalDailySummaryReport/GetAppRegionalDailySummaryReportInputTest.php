<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Application\UseCase\Query\GetAppRegionalDailySummaryReport;

use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Application\UseCase\Query\GetAppRegionalDailySummaryReport\GetAppRegionalDailySummaryReportInput;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Domain\ValueObject\RegionalSelection;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Shared\Domain\ValueObject\CountryCode;
use Tests\Helper\AnalyzeAppMetrics;
use Tests\Helper\StrTestHelper;

class GetAppRegionalDailySummaryReportInputTest extends TestCase
{
    public function testPreservesTypedInputInstances(): void
    {
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $period = new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-01'), AnalyzeAppMetrics::now());
        $selection = new RegionalSelection(CountryCode::JAPAN, 'JP-13');
        $input = new GetAppRegionalDailySummaryReportInput($principal, $period, $selection);
        self::assertSame($principal, $input->principalIdentifier());
        self::assertSame($period, $input->period());
        self::assertSame($selection, $input->selection());
        self::assertSame('JP', $input->country());
    }
}
