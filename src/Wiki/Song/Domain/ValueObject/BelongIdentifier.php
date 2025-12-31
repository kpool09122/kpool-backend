<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class BelongIdentifier extends StringBaseValue
{
    public function __construct(
        protected readonly string $id,
    ) {
        parent::__construct($id);
        $this->validate($id);
    }

    protected function validate(
        string $value,
    ): void {
        if (! UuidValidator::isValid($value)) {
            throw new InvalidArgumentException();
        }
    }
}
