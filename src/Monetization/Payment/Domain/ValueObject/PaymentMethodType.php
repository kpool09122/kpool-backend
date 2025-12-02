<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\ValueObject;

enum PaymentMethodType: string
{
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case WALLET = 'wallet';
}
