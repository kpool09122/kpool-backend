<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Query\GetWikiRegionalDailySummaryReport;

use Source\Analyze\Shared\Domain\ValueObject\RegionalSelection;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInputPort;

interface GetWikiRegionalDailySummaryReportInputPort extends GetWikiDailySummaryReportInputPort
{
    public function selection(): RegionalSelection;
}
