<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query\GetAppRegionalDailySummaryReport;

use Source\Analyze\Shared\Application\UseCase\Query\RegionalReportReadModel;
use Source\Analyze\Shared\Domain\Exception\UnauthorizedException;

interface GetAppRegionalDailySummaryReportInterface
{
    /** @throws UnauthorizedException */
    public function process(GetAppRegionalDailySummaryReportInputPort $input): RegionalReportReadModel;
}
