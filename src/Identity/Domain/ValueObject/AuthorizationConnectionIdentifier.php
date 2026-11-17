<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;
use Symfony\Component\Uid\Uuid;

final class AuthorizationConnectionIdentifier extends StringBaseValue
{
    protected function validate(string $value): void
    {
        if (! Uuid::isValid($value)) {
            throw new InvalidArgumentException('Authorization connection identifier must be a valid UUID.');
        }
    }
}
