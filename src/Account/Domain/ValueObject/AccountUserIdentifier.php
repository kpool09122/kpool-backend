<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class AccountUserIdentifier extends StringBaseValue
{
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->validate($id);
    }

    protected function validate(string $value): void
    {
        if (! UlidValidator::isValid($value)) {
            throw new InvalidArgumentException('Invalid AccountUserIdentifier.');
        }
    }
}
