<?php

declare(strict_types=1);

namespace Businesses\Wiki\Group\Domain\ValueObject;

use Businesses\Shared\Service\Ulid\UlidValidator;
use Businesses\Shared\ValueObject\Foundation\StringBaseValue;
use InvalidArgumentException;

class GroupIdentifier extends StringBaseValue
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
        if (! UlidValidator::isValid($value)) {
            throw new InvalidArgumentException();
        }
    }
}
