<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

enum BillingCycle: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';
    case LIFETIME = 'lifetime';
}
