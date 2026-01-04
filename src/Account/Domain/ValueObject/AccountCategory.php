<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

enum AccountCategory: string
{
    case AGENCY = 'agency';
    case TALENT = 'talent';
    case GENERAL = 'general';

    public function isAgency(): bool
    {
        return $this === self::AGENCY;
    }

    public function isTalent(): bool
    {
        return $this === self::TALENT;
    }

    public function isGeneral(): bool
    {
        return $this === self::GENERAL;
    }
}
