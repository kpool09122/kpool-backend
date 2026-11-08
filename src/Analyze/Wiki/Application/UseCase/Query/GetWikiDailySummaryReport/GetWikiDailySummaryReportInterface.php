<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport;

use Source\Analyze\Shared\Domain\Exception\UnauthorizedException;
use Source\Analyze\Wiki\Application\UseCase\Query\WikiDailySummaryReportReadModel;

interface GetWikiDailySummaryReportInterface
{
    /** @throws UnauthorizedException */
    public function process(GetWikiDailySummaryReportInputPort $input): WikiDailySummaryReportReadModel;
}
