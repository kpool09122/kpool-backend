<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class ExternalContentLink extends StringBaseValue
{
    public function __construct(
        protected readonly string $uri,
    ) {
        parent::__construct($uri);
        $this->validate($uri);
    }

    protected function validate(
        string $value,
    ): void {
        if (! str_starts_with($value, 'https://') || ! filter_var($value, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("The URI '{$value}' is not a valid URL.");
        }
    }
}
