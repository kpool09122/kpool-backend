<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class ContractName extends StringBaseValue
{
    public const int MAX_LENGTH = 64;

    public function __construct(string $value)
    {
        parent::__construct(trim($value));
    }

    protected function validate(string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException('ContactName cannot be empty.');
        }
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('ContactName cannot be longer than ' . self::MAX_LENGTH . ' characters.');
        }
    }
}
