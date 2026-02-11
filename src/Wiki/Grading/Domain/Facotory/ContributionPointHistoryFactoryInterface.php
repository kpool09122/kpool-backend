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
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface ContributionPointHistoryFactoryInterface
{
    public function create(
        PrincipalIdentifier $principalIdentifier,
        YearMonth           $yearMonth,
        Point               $points,
        ResourceType        $resourceType,
        WikiIdentifier      $wikiIdentifier,
        ContributorType     $roleType,
        bool                $isNewCreation,
        DateTimeImmutable   $createdAt,
    ): ContributionPointHistory;
}
