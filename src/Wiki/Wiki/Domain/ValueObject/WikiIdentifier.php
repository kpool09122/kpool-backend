<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class WikiIdentifier extends StringBaseValue
{
    protected function validate(
        string $value,
    ): void {
        if (! UuidValidator::isValid($value)) {
            throw new InvalidArgumentException();
        }
    }
}
