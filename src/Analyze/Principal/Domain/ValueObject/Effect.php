<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Domain\ValueObject;

enum Effect: string
{
    case ALLOW = 'allow';
    case DENY = 'deny';
}
