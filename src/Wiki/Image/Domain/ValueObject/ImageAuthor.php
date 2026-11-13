<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

final class ImageAuthor extends StringBaseValue
{
    public const int MAX_LENGTH = 255;

    public function __construct(string $value)
    {
        parent::__construct(mb_trim($value));
    }

    protected function validate(string $value): void
    {
        if ($value === '' || mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Image author must contain 1 to 255 characters.');
        }
    }
}
