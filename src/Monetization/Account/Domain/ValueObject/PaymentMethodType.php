<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

enum PaymentMethodType: string
{
    case CARD = 'card';
}
