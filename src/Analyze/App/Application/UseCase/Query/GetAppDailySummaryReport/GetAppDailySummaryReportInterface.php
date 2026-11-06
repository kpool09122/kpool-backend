<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport;

use Source\Analyze\App\Application\UseCase\Query\AppDailySummaryReportReadModel;

interface GetAppDailySummaryReportInterface
{
    public function process(GetAppDailySummaryReportInputPort $input): AppDailySummaryReportReadModel;
}
