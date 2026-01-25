<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Factory;

use Source\Wiki\Principal\Domain\Entity\ContributionPointSummary;
use Source\Wiki\Principal\Domain\ValueObject\Point;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ContributionPointSummaryFactoryInterface
{
    public function create(
        PrincipalIdentifier $principalIdentifier,
        YearMonth $yearMonth,
        Point $points,
    ): ContributionPointSummary;
}
