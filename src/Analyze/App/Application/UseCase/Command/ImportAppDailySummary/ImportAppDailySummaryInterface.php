<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary;

use Source\Analyze\App\Application\Exception\IncompleteDailyMetricsException;
use Source\Analyze\App\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\App\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;

interface ImportAppDailySummaryInterface
{
    /**
     * @throws InvalidDailySummaryException
     * @throws IncompleteDailyMetricsException
     * @throws MetricsFetchFailedException
     * @throws MetricDefinitionMismatchException
     */
    public function process(ImportAppDailySummaryInputPort $input, ImportAppDailySummaryOutputPort $output): void;
}
