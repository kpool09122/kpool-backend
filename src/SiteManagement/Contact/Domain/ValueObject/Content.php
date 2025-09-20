<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class Content extends StringBaseValue
{
    public const int MAX_LENGTH = 512;

    public function __construct(
        protected readonly string $name,
    ) {
        parent::__construct($name);
        $this->validate($name);
    }

    protected function validate(
        string $value,
    ): void {
        if (empty($value)) {
            throw new InvalidArgumentException('Content cannot be empty');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Content cannot exceed ' . self::MAX_LENGTH . ' characters');
        }
    }
}
