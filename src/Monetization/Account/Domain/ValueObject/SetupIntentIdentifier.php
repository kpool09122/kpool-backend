<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class SetupIntentIdentifier extends StringBaseValue
{
    protected function validate(string $value): void
    {
        if (! preg_match('/^seti_[A-Za-z0-9]+$/D', $value)) {
            throw new InvalidArgumentException('Invalid SetupIntent identifier.');
        }
    }
}
