<?php

namespace Businesses\Shared\ValueObject;

use Businesses\Shared\ValueObject\Foundation\StringBaseValue;

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
