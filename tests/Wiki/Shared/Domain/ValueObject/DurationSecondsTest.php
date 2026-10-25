<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;

class DurationSecondsTest extends TestCase
{
    public function testAcceptsBounds(): void
    {
        self::assertSame(0, (new DurationSeconds(0))->value());
        self::assertSame(2147483647, (new DurationSeconds(2147483647))->value());
    }

    /** @return array<string, array{int}> */
    public static function invalidValues(): array
    {
        return ['negative' => [-1], 'overflow' => [2147483648]];
    }

    #[DataProvider('invalidValues')]
    public function testRejectsOutOfRangeValue(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DurationSeconds($value);
    }
}
