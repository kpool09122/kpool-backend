<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport;

use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;

interface GetAppDailySummaryReportInputPort
{
    public function definition(): MetricDefinition;

    public function period(): ReportingPeriod;
}
