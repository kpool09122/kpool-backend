<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Domain\ValueObject;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Stringable;

readonly class UtcDate implements Stringable
{
    private DateTimeImmutable $value;

    public function __construct(string $value)
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
        if ($date === false || $date->format('Y-m-d') !== $value || $date->format('Y') < '0001') {
            throw new InvalidArgumentException('Expected a UTC date in YYYY-MM-DD format.');
        }
        $this->value = $date;
    }

    public function instant(): DateTimeImmutable
    {
        return $this->value;
    }

    public function next(): self
    {
        return new self($this->value->modify('+1 day')->format('Y-m-d'));
    }

    public function __toString(): string
    {
        return $this->value->format('Y-m-d');
    }
}
