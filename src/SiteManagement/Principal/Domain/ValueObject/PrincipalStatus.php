<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\ValueObject;

enum PrincipalStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case FORCED_STOP = 'forced_stop';
}
