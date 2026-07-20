<?php

declare(strict_types=1);

namespace Source\Account\Policy\Domain\ValueObject;

enum Effect: string
{
    case ALLOW = 'allow';
    case DENY = 'deny';
}
