<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

enum AccountType: string
{
    case CORPORATION = 'corporation';
    case INDIVIDUAL = 'individual';
}
