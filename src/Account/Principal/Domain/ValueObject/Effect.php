<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

enum Effect: string
{
    case ALLOW = 'allow';
    case DENY = 'deny';
}
