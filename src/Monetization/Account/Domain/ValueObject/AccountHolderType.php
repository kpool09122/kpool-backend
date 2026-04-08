<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

enum AccountHolderType: string
{
    case INDIVIDUAL = 'individual';
    case COMPANY = 'company';
}
