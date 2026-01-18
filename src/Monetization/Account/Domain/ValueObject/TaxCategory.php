<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

enum TaxCategory: string
{
    case TAXABLE = 'taxable';
    case EXEMPT = 'exempt';
    case REVERSE_CHARGE = 'reverse_charge';
}
