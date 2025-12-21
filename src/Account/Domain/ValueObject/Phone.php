<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

/**
 * 汎用電話番号 VO.
 * E.164 をベースに、桁数 7〜15 の数字を許容し、先頭に+を付けられる。
 * スペース/ハイフン/カッコは取り除いて保存する。
 */
class Phone extends StringBaseValue
{
    public const int MIN_DIGITS = 7;
    public const int MAX_DIGITS = 15;

    public function __construct(string $value)
    {
        parent::__construct($this->normalize($value));
    }

    protected function validate(string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException('Phone number is required.');
        }

        if (! preg_match('/^\+?[0-9]+$/', $value)) {
            throw new InvalidArgumentException('Phone number must contain only digits with an optional leading +.');
        }

        $digits = ltrim($value, '+');
        $length = strlen($digits);
        if ($length < self::MIN_DIGITS || $length > self::MAX_DIGITS) {
            throw new InvalidArgumentException(
                sprintf('Phone number must be between %d and %d digits.', self::MIN_DIGITS, self::MAX_DIGITS)
            );
        }
    }

    private function normalize(string $value): string
    {
        $trimmed = trim($value);

        // remove common separators; keep leading plus if present
        return preg_replace('/[\\s\\-()]/', '', $trimmed) ?? '';
    }
}
