<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

enum TaxCategory: string
{
    case TAXABLE = 'taxable';
    case EXEMPT = 'exempt';
    case REVERSE_CHARGE = 'reverse_charge';
}
