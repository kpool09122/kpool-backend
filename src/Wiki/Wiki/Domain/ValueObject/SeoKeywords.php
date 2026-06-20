<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;

final readonly class SeoKeywords
{
    public const int MAX_COUNT = 5;
    public const int MAX_KEYWORD_LENGTH = 20;

    /** @var list<string> */
    private array $values;

    /**
     * @param list<string> $values
     */
    public function __construct(array $values)
    {
        if (count($values) > self::MAX_COUNT) {
            throw new InvalidArgumentException('SEO keywords cannot exceed ' . self::MAX_COUNT . ' items.');
        }

        foreach ($values as $value) {
            if ($value === '') {
                throw new InvalidArgumentException('SEO keyword cannot be empty.');
            }

            if (mb_strlen($value) > self::MAX_KEYWORD_LENGTH) {
                throw new InvalidArgumentException(
                    'SEO keyword cannot exceed ' . self::MAX_KEYWORD_LENGTH . ' characters.'
                );
            }
        }

        $this->values = array_values($values);
    }

    /**
     * @return list<string>
     */
    public function values(): array
    {
        return $this->values;
    }
}
