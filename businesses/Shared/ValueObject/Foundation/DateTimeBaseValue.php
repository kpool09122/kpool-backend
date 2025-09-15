<?php

declare(strict_types=1);

namespace Businesses\Shared\ValueObject\Foundation;

use DateTimeImmutable;
use InvalidArgumentException;

abstract class DateTimeBaseValue
{
    public function __construct(
        protected readonly DateTimeImmutable $value,
    ) {
        $this->validate($value);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value->format(DateTimeImmutable::RFC3339_EXTENDED);
    }

    /**
     * @param DateTimeImmutable $target
     * @return bool
     */
    public function isPastDate(DateTimeImmutable $target): bool
    {
        return $this->value < $target;
    }

    /**
     * @param DateTimeImmutable $target
     * @return bool
     */
    public function isFutureDate(DateTimeImmutable $target): bool
    {
        return $this->value > $target;
    }

    /**
     * @return DateTimeImmutable
     */
    public function value(): DateTimeImmutable
    {
        return $this->value;
    }

    /**
     * @param string $format
     * @return string
     */
    public function format(string $format): string
    {
        return $this->value->format($format);
    }

    /**
     * @param DateTimeImmutable $value
     * @throws InvalidArgumentException
     * @return void
     */
    abstract protected function validate(DateTimeImmutable $value): void;
}
