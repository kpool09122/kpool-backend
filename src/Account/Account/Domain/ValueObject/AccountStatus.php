<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

enum AccountStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
}
