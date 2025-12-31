<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class PaymentMethodIdentifier extends StringBaseValue
{
    protected function validate(string $value): void
    {
        if (! UuidValidator::isValid($value)) {
            throw new InvalidArgumentException('Payment method id must be ULID format.');
        }
    }
}
