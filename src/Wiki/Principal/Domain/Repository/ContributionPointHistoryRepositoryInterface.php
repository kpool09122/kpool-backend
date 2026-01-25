<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Repository;

use DateTimeImmutable;
use Source\Wiki\Principal\Domain\Entity\ContributionPointHistory;
use Source\Wiki\Principal\Domain\ValueObject\ContributorType;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface ContributionPointHistoryRepositoryInterface
{
    public function save(ContributionPointHistory $history): void;

    /**
     * @return ContributionPointHistory[]
     */
    public function findByPrincipalAndYearMonth(
        PrincipalIdentifier $principalIdentifier,
        YearMonth $yearMonth,
    ): array;

    /**
     * @return ContributionPointHistory[]
     */
    public function findByYearMonth(YearMonth $yearMonth): array;

    /**
     * Get the last publish date for cooldown check.
     * Used to determine if the same editor on the same resource should receive points.
     */
    public function findLastPublishDate(
        PrincipalIdentifier $principalIdentifier,
        ResourceType        $resourceType,
        string              $resourceId,
        ContributorType     $contributorType,
    ): ?DateTimeImmutable;
}
