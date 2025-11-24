<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

enum BillingCycle: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';
    case LIFETIME = 'lifetime';
}
