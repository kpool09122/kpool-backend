<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class DocumentPath extends StringBaseValue
{
    public const int MAX_LENGTH = 512;

    public function __construct(string $path)
    {
        parent::__construct($path);
        $this->validate($path);
    }

    protected function validate(string $value): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('DocumentPath cannot be empty.');
        }

        if (strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('DocumentPath cannot exceed ' . self::MAX_LENGTH . ' characters.');
        }
    }
}
