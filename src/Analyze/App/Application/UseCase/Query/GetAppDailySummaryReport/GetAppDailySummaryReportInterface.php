<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport;

use Source\Analyze\App\Application\UseCase\Query\AppDailySummaryReportReadModel;
use Source\Analyze\Shared\Domain\Exception\UnauthorizedException;

interface GetAppDailySummaryReportInterface
{
    /** @throws UnauthorizedException */
    public function process(GetAppDailySummaryReportInputPort $input): AppDailySummaryReportReadModel;
}
