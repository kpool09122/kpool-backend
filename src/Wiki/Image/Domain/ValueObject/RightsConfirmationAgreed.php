<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\ValueObject;

use InvalidArgumentException;

readonly class RightsConfirmationAgreed
{
    public function __construct(private bool $value)
    {
        if (! $value) {
            throw new InvalidArgumentException('Rights confirmation must be agreed.');
        }
    }

    public function value(): bool
    {
        return $this->value;
    }
}
