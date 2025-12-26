<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class PlainPassword extends StringBaseValue
{
    public const int MIN_LENGTH = 8;
    public const int MAX_LENGTH = 20;
    public const string ALLOWED_CHARACTERS_PATTERN = '/\A[0-9A-Za-z!"#$%&\'()\-^@\[;:\],.\/=~|`{+*}<>?_]+\z/';

    public function __construct(
        protected string $id,
    ) {
        parent::__construct($id);
        $this->validate($id);
    }

    protected function validate(
        string $value,
    ): void {
        $length = strlen($value);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Password length is invalid.');
        }

        if (! preg_match(self::ALLOWED_CHARACTERS_PATTERN, $value)) {
            throw new InvalidArgumentException('Password contains invalid characters.');
        }
    }
}
