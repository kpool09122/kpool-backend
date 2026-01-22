<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Repository;

use Source\Wiki\Principal\Domain\Entity\ContributionPointSummary;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ContributionPointSummaryRepositoryInterface
{
    public function save(ContributionPointSummary $summary): void;

    public function findByPrincipalAndYearMonth(
        PrincipalIdentifier $principalIdentifier,
        YearMonth $yearMonth,
    ): ?ContributionPointSummary;

    /**
     * @return ContributionPointSummary[]
     */
    public function findByYearMonth(YearMonth $yearMonth): array;

    /**
     * @param YearMonth[] $yearMonths
     * @return ContributionPointSummary[]
     */
    public function findByYearMonths(array $yearMonths): array;
}
