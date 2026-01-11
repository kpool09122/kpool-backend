<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

enum AccountType: string
{
    case CORPORATION = 'corporation';
    case INDIVIDUAL = 'individual';
}
