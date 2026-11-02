<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\Service;

use DateTimeImmutable;
use Source\Analyze\App\Application\Exception\IncompleteDailyMetricsException;
use Source\Analyze\App\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

interface DailyMetricsFetchServiceInterface
{
    /**
     * @throws IncompleteDailyMetricsException
     * @throws MetricsFetchFailedException
     */
    public function fetchSummary(UtcDate $day, DateTimeImmutable $now, int $maxPages): AppDailySummaryData;
}
