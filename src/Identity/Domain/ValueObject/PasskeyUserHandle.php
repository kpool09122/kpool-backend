<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class PasskeyUserHandle extends StringBaseValue
{
    protected function validate(string $value): void
    {
        if (! UuidValidator::isValid($value)) {
            throw new InvalidArgumentException('Passkey user handle must be a UUIDv7.');
        }
    }
}
