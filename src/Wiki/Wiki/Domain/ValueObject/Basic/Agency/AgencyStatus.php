<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency;

enum AgencyStatus: string
{
    case ACTIVE = 'active';
    case CLOSED = 'closed';
    case MERGED = 'merged';
    case REBRANDED = 'rebranded';
}
