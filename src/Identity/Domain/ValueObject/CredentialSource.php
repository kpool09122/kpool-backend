<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use InvalidArgumentException;
use JsonException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class CredentialSource extends StringBaseValue
{
    protected function validate(string $value): void
    {
        try {
            $decoded = json_decode($value, false, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Credential source must be valid JSON.', previous: $exception);
        }
        if (! is_object($decoded)) {
            throw new InvalidArgumentException('Credential source must be a JSON object.');
        }
    }
}
