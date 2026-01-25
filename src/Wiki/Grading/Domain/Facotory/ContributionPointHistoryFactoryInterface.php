<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\Facotory;

use DateTimeImmutable;
use Source\Wiki\Grading\Domain\Entity\ContributionPointHistory;
use Source\Wiki\Grading\Domain\ValueObject\ContributorType;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
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
