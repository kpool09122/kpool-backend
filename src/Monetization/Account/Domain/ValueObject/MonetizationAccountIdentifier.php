<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class MonetizationAccountIdentifier extends StringBaseValue
{
    public function __construct(
        protected string $id,
    ) {
        parent::__construct($id);
        $this->validate($id);
    }

    protected function validate(string $value): void
    {
        if (! UuidValidator::isValid($value)) {
            throw new InvalidArgumentException('Invalid MonetizationAccountIdentifier.');
        }
    }
}
