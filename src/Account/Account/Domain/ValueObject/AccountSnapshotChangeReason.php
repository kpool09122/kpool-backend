<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

readonly class AccountSnapshotChangeReason implements Stringable
{
    public function __construct(private string $value)
    {
        if (mb_trim($value) === '' || mb_strlen($value) > 2000) {
            throw new InvalidArgumentException('Change reason must contain between 1 and 2000 characters.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
