<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\ValueObject;

enum BillingPeriod: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case ANNUAL = 'annual';
}
