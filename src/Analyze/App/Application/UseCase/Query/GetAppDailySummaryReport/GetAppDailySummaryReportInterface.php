<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport;

use Source\Analyze\App\Application\Exception\EmptyReportingPeriodException;
use Source\Analyze\App\Application\UseCase\Query\AppDailySummaryReportReadModel;
use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;

interface GetAppDailySummaryReportInterface
{
    /**
     * @throws EmptyReportingPeriodException
     * @throws MetricDefinitionMismatchException
     */
    public function process(GetAppDailySummaryReportInputPort $input): AppDailySummaryReportReadModel;
}
