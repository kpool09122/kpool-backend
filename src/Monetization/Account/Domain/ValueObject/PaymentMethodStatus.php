<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

enum PaymentMethodStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
