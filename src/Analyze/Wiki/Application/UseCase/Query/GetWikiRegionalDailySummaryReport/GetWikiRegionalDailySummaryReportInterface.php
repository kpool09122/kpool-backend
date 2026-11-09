<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Query\GetWikiRegionalDailySummaryReport;

use Source\Analyze\Shared\Application\UseCase\Query\RegionalReportReadModel;
use Source\Analyze\Shared\Domain\Exception\UnauthorizedException;

interface GetWikiRegionalDailySummaryReportInterface
{
    /** @throws UnauthorizedException */
    public function process(GetWikiRegionalDailySummaryReportInputPort $input): RegionalReportReadModel;
}
