<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;
use Symfony\Component\Uid\Uuid;

readonly class AccountCategoryChangeRequestIdentifier implements Stringable
{
    public function __construct(private string $value)
    {
        if (! Uuid::isValid($value)) {
            throw new InvalidArgumentException('Account category change request identifier must be a valid UUID.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
