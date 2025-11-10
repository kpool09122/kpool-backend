<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class AutomaticDraftGroupSource extends StringBaseValue
{
    public const int MAX_LENGTH = 255;

    public function __construct(
        protected readonly string $source,
    ) {
        parent::__construct($source);
        $this->validate($source);
    }

    protected function validate(
        string $value,
    ): void {
        if ($value === '') {
            throw new InvalidArgumentException('Automatic draft group source cannot be empty');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Automatic draft group source cannot exceed ' . self::MAX_LENGTH . ' characters');
        }
    }
}
