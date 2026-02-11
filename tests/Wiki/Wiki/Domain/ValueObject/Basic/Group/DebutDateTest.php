<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Group;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\DebutDate;

class DebutDateTest extends TestCase
{
    /**
     * 正常系: DateTimeImmutableでインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $dateTime = new DateTimeImmutable('2020-01-15 00:00:00');
        $debutDate = new DebutDate($dateTime);

        $this->assertSame($dateTime, $debutDate->value());
    }

    /**
     * 正常系: value()でDateTimeImmutableが取得できること
     */
    public function testValue(): void
    {
        $dateTime = new DateTimeImmutable('2023-03-21 12:00:00');
        $debutDate = new DebutDate($dateTime);

        $this->assertSame('2023-03-21', $debutDate->value()->format('Y-m-d'));
    }

    /**
     * 正常系: __toString()でRFC3339_EXTENDED形式の文字列が取得できること
     */
    public function test__toString(): void
    {
        $dateTime = new DateTimeImmutable('2020-01-15 00:00:00.000+09:00');
        $debutDate = new DebutDate($dateTime);

        $this->assertSame($dateTime->format(DateTimeImmutable::RFC3339_EXTENDED), (string) $debutDate);
    }

    /**
     * 正常系: format()で指定したフォーマットで文字列が取得できること
     */
    public function testFormat(): void
    {
        $dateTime = new DateTimeImmutable('2020-01-15 00:00:00');
        $debutDate = new DebutDate($dateTime);

        $this->assertSame('2020-01-15', $debutDate->format('Y-m-d'));
        $this->assertSame('2020年01月15日', $debutDate->format('Y年m月d日'));
    }

    /**
     * 正常系: isPastDate()で過去日判定が正しく動作すること
     */
    public function testIsPastDate(): void
    {
        $debutDate = new DebutDate(new DateTimeImmutable('2020-01-15 00:00:00'));

        $futureDate = new DateTimeImmutable('2025-01-01 00:00:00');
        $pastDate = new DateTimeImmutable('2019-01-01 00:00:00');
        $sameDate = new DateTimeImmutable('2020-01-15 00:00:00');

        $this->assertTrue($debutDate->isPastDate($futureDate));
        $this->assertFalse($debutDate->isPastDate($pastDate));
        $this->assertFalse($debutDate->isPastDate($sameDate));
    }

    /**
     * 正常系: isFutureDate()で未来日判定が正しく動作すること
     */
    public function testIsFutureDate(): void
    {
        $debutDate = new DebutDate(new DateTimeImmutable('2020-01-15 00:00:00'));

        $futureDate = new DateTimeImmutable('2025-01-01 00:00:00');
        $pastDate = new DateTimeImmutable('2019-01-01 00:00:00');
        $sameDate = new DateTimeImmutable('2020-01-15 00:00:00');

        $this->assertFalse($debutDate->isFutureDate($futureDate));
        $this->assertTrue($debutDate->isFutureDate($pastDate));
        $this->assertFalse($debutDate->isFutureDate($sameDate));
    }
}
