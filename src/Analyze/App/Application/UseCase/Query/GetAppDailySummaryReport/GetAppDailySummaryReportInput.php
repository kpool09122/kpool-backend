<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport;

use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;

readonly class GetAppDailySummaryReportInput implements GetAppDailySummaryReportInputPort
{
    public function __construct(
        private ReportingPeriod $period,
    ) {
    }

    public function period(): ReportingPeriod
    {
        return $this->period;
    }
}
