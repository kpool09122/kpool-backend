<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleAnalyticsClient\RunReport;

use InvalidArgumentException;

/** A GA4 dimension filter expression; field names and values are supplied by the caller. */
final readonly class FilterExpression
{
    /** @param array<string, mixed> $expression */
    private function __construct(private array $expression)
    {
    }

    /** @param non-empty-list<string> $values */
    public static function inList(string $field, array $values, bool $caseSensitive = false): self
    {
        if ($field === '' || $values === []) {
            throw new InvalidArgumentException('A filter requires a field and values.');
        }

        return new self(['filter' => ['fieldName' => $field, 'inListFilter' => ['values' => $values, 'caseSensitive' => $caseSensitive]]]);
    }

    public static function exact(string $field, string $value, bool $caseSensitive = false): self
    {
        return self::stringFilter($field, $value, 'EXACT', $caseSensitive);
    }

    public static function fullRegexp(string $field, string $value, bool $caseSensitive = true): self
    {
        return self::stringFilter($field, $value, 'FULL_REGEXP', $caseSensitive);
    }

    public static function negate(self $filter): self
    {
        return new self(['notExpression' => $filter->toArray()]);
    }

    public static function all(self ...$filters): self
    {
        if ($filters === []) {
            throw new InvalidArgumentException('An AND group requires filters.');
        }

        return new self(['andGroup' => ['expressions' => array_map(static fn (self $filter): array => $filter->toArray(), $filters)]]);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->expression;
    }

    private static function stringFilter(string $field, string $value, string $matchType, bool $caseSensitive): self
    {
        if ($field === '') {
            throw new InvalidArgumentException('A filter requires a field.');
        }

        return new self(['filter' => ['fieldName' => $field, 'stringFilter' => ['matchType' => $matchType, 'value' => $value, 'caseSensitive' => $caseSensitive]]]);
    }
}
