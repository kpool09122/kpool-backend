<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\Facotory;

use Source\Wiki\Grading\Domain\Entity\ContributionPointSummary;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ContributionPointSummaryFactoryInterface
{
    public function create(
        PrincipalIdentifier $principalIdentifier,
        YearMonth $yearMonth,
        Point $points,
    ): ContributionPointSummary;
}
