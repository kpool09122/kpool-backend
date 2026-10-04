<?php

declare(strict_types=1);

namespace Application\Http\RateLimit;

enum RateLimitSurface: string
{
    case SCREEN = 'screen';
    case PUBLIC_API = 'public_api';
}
