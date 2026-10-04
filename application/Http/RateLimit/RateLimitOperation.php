<?php

declare(strict_types=1);

namespace Application\Http\RateLimit;

enum RateLimitOperation: string
{
    case QUERY = 'query';
    case COMMAND = 'command';
}
