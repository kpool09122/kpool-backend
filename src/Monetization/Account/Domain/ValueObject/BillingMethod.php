<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

enum BillingMethod: string
{
    case INVOICE = 'invoice';
    case CREDIT_CARD = 'credit_card';
    case BANK_TRANSFER = 'bank_transfer';
}
