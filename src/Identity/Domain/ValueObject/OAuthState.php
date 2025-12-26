<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class OAuthState extends StringBaseValue
{
    public const int MAX_LENGTH = 255;

    public function __construct(
        string $value,
        private readonly DateTimeImmutable $expiresAt,
    ) {
        parent::__construct($value);
    }

    protected function validate(string $value): void
    {
        $length = mb_strlen($value);
        if ($length === 0 || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException('OAuth state must be between 1 and ' . self::MAX_LENGTH . ' characters.');
        }
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
