<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

enum Currency: string
{
    case JPY = 'JPY';
    case USD = 'USD';
    case KRW = 'KRW';
}
