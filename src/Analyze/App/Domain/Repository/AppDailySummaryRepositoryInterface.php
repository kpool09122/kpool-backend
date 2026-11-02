<?php

declare(strict_types=1);

namespace Source\Analyze\App\Domain\Repository;

use Source\Analyze\App\Domain\Entity\AppDailySummary;
use Source\Analyze\App\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

interface AppDailySummaryRepositoryInterface
{
    /**
     * @throws InvalidDailySummaryException
     * @throws MetricDefinitionMismatchException
     */
    public function findByDay(UtcDate $day): ?AppDailySummary;

    public function save(AppDailySummary $summary): void;
}
