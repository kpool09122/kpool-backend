<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

final class ThumbnailUrl extends StringBaseValue
{
    public const int MAX_LENGTH = 255;

    public function __construct(string $value)
    {
        parent::__construct($value);
        $this->validate($value);
    }

    protected function validate(string $value): void
    {
        if (mb_strlen($value) > self::MAX_LENGTH || ! filter_var($value, FILTER_VALIDATE_URL) || ! in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new InvalidArgumentException('Thumbnail URL must be an HTTP or HTTPS URL of at most ' . self::MAX_LENGTH . ' characters.');
        }
    }
}
