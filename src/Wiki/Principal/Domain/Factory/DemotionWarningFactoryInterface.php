<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Factory;

use Source\Wiki\Principal\Domain\Entity\DemotionWarning;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface DemotionWarningFactoryInterface
{
    public function create(
        PrincipalIdentifier $principalIdentifier,
        YearMonth $lastWarningMonth,
    ): DemotionWarning;
}
