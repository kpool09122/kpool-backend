<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport;

use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;
use Source\Analyze\Wiki\Application\UseCase\Query\WikiDailySummaryReportReadModel;

interface GetWikiDailySummaryReportInterface
{
    /** @throws MetricDefinitionMismatchException */
    public function process(GetWikiDailySummaryReportInputPort $input): WikiDailySummaryReportReadModel;
}
