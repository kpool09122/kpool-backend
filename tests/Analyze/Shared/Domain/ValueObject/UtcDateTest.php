<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

class UtcDateTest extends TestCase
{
    public function testStrictDatesAndUtcBoundary(): void
    {
        $date = new UtcDate('2020-02-29');
        self::assertSame('2020-03-01', (string) $date->next());
        self::assertSame('+00:00', $date->instant()->format('P'));
        $this->expectException(InvalidArgumentException::class);
        new UtcDate('2021-02-29');
    }

    public function testTimestampIsNotADate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UtcDate('2020-01-01T00:00:00Z');
    }
}
