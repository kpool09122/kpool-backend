<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class InvoiceIdentifier extends StringBaseValue
{
    protected function validate(string $value): void
    {
        if (! UlidValidator::isValid($value)) {
            throw new InvalidArgumentException('Invoice id must be ULID format.');
        }
    }
}
