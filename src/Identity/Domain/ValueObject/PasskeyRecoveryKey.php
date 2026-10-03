<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use InvalidArgumentException;

readonly class PasskeyRecoveryKey
{
    public function __construct(private string $value)
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
            throw new InvalidArgumentException('Passkey recovery key must be a UUID.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
