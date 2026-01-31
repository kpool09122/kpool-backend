<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class ContactReplyIdentifier extends StringBaseValue
{
    protected function validate(string $value): void
    {
        if (! UuidValidator::isValid($value)) {
            throw new InvalidArgumentException('Invalid ContactReplyIdentifier.');
        }
    }
}
