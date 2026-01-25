<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class YearMonth extends StringBaseValue
{
    protected function validate(string $value): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            throw new InvalidArgumentException('YearMonth must be in YYYY-MM format.');
        }
    }

    public static function fromDateTime(DateTimeImmutable $dateTime): self
    {
        return new self($dateTime->format('Y-m'));
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function current(): self
    {
        return self::fromDateTime(new DateTimeImmutable());
    }

    public function year(): int
    {
        return (int) substr($this->value, 0, 4);
    }

    public function month(): int
    {
        return (int) substr($this->value, 5, 2);
    }

    public function subtract(int $months): self
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $this->value . '-01');
        if ($date === false) {
            throw new InvalidArgumentException('Invalid YearMonth format.');
        }

        $newDate = $date->modify("-{$months} months");

        return new self($newDate->format('Y-m'));
    }

    public function toFirstDayOfMonth(): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->value . '-01 00:00:00');
        if ($date === false) {
            throw new InvalidArgumentException('Invalid YearMonth format.');
        }

        return $date;
    }
}
