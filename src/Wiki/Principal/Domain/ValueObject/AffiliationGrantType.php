<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

enum AffiliationGrantType: string
{
    case TALENT_SIDE = 'talent_side';
    case AGENCY_SIDE = 'agency_side';
}
