<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

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
    }
}
