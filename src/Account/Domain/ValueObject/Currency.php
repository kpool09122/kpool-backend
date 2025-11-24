<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

enum Currency: string
{
    case JYP = 'JPY';
    case USD = 'USD';
    case KRW = 'KRW';
}
