<?php

namespace Businesses\Member\Domain\ValueObject;

use Businesses\Shared\ValueObject\Foundation\StringBaseValue;

class ImageLink extends StringBaseValue
{
    public function __construct(
        protected readonly string $link,
    ) {
        parent::__construct($link);
        $this->validate($link);
    }

    protected function validate(
        string $value,
    ): void {
    }
}
