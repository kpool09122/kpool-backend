<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

enum TaxRegion: string
{
    case JP = 'JP';
    case KR = 'KR';
    case US = 'US';
}
