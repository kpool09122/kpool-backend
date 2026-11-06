<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport;

use Source\Analyze\Wiki\Application\UseCase\Query\WikiDailySummaryReportReadModel;

interface GetWikiDailySummaryReportInterface
{
    public function process(GetWikiDailySummaryReportInputPort $input): WikiDailySummaryReportReadModel;
}
