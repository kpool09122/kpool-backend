<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class ImagePath extends StringBaseValue
{
    public function __construct(
        protected readonly string $path,
    ) {
        parent::__construct($path);
        $this->validate($path);
    }

    protected function validate(
        string $value,
    ): void {
        $lowered = strtolower($value);

        if (str_starts_with($lowered, 'http://') || str_starts_with($lowered, 'https://') || str_starts_with($lowered, '//')) {
            throw new InvalidArgumentException('External URLs are not allowed for ImagePath.');
        }
    }
}
