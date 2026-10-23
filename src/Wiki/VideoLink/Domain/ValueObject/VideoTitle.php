<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

final class VideoTitle extends StringBaseValue
{
    public const int MAX_LENGTH = 255;

    public function __construct(string $value)
    {
        parent::__construct($value);
        $this->validate($value);
    }

    protected function validate(string $value): void
    {
        if (trim($value) === '' || mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Video title must contain between 1 and ' . self::MAX_LENGTH . ' characters.');
        }
    }
}
