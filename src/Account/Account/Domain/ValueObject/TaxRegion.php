<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

enum TaxRegion: string
{
    case JP = 'JP';
    case KR = 'KR';
    case US = 'US';
}
