<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Factory;

use DateTimeImmutable;
use Source\Wiki\Principal\Domain\Entity\ContributionPointHistory;
use Source\Wiki\Principal\Domain\ValueObject\ContributorType;
use Source\Wiki\Principal\Domain\ValueObject\Point;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface ContributionPointHistoryFactoryInterface
{
    public function create(
        PrincipalIdentifier $principalIdentifier,
        YearMonth           $yearMonth,
        Point               $points,
        ResourceType        $resourceType,
        string              $resourceId,
        ContributorType     $roleType,
        bool                $isNewCreation,
        DateTimeImmutable   $createdAt,
    ): ContributionPointHistory;
}
