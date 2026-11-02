<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary;

use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;
use Source\Analyze\Wiki\Application\Exception\IncompleteDailyMetricsException;
use Source\Analyze\Wiki\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;

interface ImportWikiDailySummaryInterface
{
    /**
     * @throws InvalidDailySummaryException
     * @throws IncompleteDailyMetricsException
     * @throws MetricsFetchFailedException
     * @throws MetricDefinitionMismatchException
     */
    public function process(ImportWikiDailySummaryInputPort $input, ImportWikiDailySummaryOutputPort $output): void;
}
