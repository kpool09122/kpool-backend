<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Domain\Repository;

use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Domain\Entity\WikiDailySummary;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;

interface WikiDailySummaryRepositoryInterface
{
    /**
     * @return list<WikiDailySummary>
     * @throws InvalidDailySummaryException
     * @throws MetricDefinitionMismatchException
     */
    public function findAllByDay(UtcDate $day): array;

    public function save(WikiDailySummary $summary): void;
}
