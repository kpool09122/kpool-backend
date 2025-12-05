<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class BillingCycleIdentifier extends StringBaseValue
{
    protected function validate(string $value): void
    {
        if (! UlidValidator::isValid($value)) {
            throw new InvalidArgumentException('BillingCycle id must be ULID format.');
        }
    }
}
