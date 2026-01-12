<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

enum PrincipalGroupType: string
{
    case DEFAULT = 'default';
    case CUSTOM = 'custom';
    case AFFILIATION_TALENT = 'affiliation_talent';
    case AFFILIATION_AGENCY = 'affiliation_agency';

    public function isAffiliationType(): bool
    {
        return in_array($this, [self::AFFILIATION_TALENT, self::AFFILIATION_AGENCY], true);
    }
}
