<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query\GetAppRegionalDailySummaryReport;

use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInputPort;
use Source\Analyze\Shared\Domain\ValueObject\RegionalSelection;

interface GetAppRegionalDailySummaryReportInputPort extends GetAppDailySummaryReportInputPort
{
    public function selection(): RegionalSelection;
}
