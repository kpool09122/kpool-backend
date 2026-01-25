<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\Facotory;

use Source\Wiki\Grading\Domain\Entity\DemotionWarning;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface DemotionWarningFactoryInterface
{
    public function create(
        PrincipalIdentifier $principalIdentifier,
        YearMonth $lastWarningMonth,
    ): DemotionWarning;
}
