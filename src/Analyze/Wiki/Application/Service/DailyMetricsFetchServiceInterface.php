<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\Service;

use DateTimeImmutable;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\Exception\IncompleteDailyMetricsException;
use Source\Analyze\Wiki\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;

interface DailyMetricsFetchServiceInterface
{
    /**
     * @throws IncompleteDailyMetricsException
     * @throws MetricsFetchFailedException
     * @throws InvalidDailySummaryException
     */
    public function fetchSummary(UtcDate $day, DateTimeImmutable $now, int $maxPages): WikiDailySummaryDataset;
}
