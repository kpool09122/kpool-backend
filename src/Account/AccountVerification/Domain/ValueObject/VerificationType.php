<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\ValueObject;

use Source\Account\Shared\Domain\ValueObject\AccountCategory;

enum VerificationType: string
{
    case TALENT = 'talent';
    case AGENCY = 'agency';

    public function isTalent(): bool
    {
        return $this === self::TALENT;
    }

    public function isAgency(): bool
    {
        return $this === self::AGENCY;
    }

    public function toAccountCategory(): AccountCategory
    {
        return match ($this) {
            self::TALENT => AccountCategory::TALENT,
            self::AGENCY => AccountCategory::AGENCY,
        };
    }
}
