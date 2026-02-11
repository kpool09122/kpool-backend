<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Group;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\DisbandDate;

class DisbandDateTest extends TestCase
{
    /**
     * 正常系: DateTimeImmutableでインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $dateTime = new DateTimeImmutable('2024-12-31 00:00:00');
        $disbandDate = new DisbandDate($dateTime);

        $this->assertSame($dateTime, $disbandDate->value());
    }

    /**
     * 正常系: value()でDateTimeImmutableが取得できること
     */
    public function testValue(): void
    {
        $dateTime = new DateTimeImmutable('2023-06-30 23:59:59');
        $disbandDate = new DisbandDate($dateTime);

        $this->assertInstanceOf(DateTimeImmutable::class, $disbandDate->value());
        $this->assertSame('2023-06-30', $disbandDate->value()->format('Y-m-d'));
    }

    /**
     * 正常系: __toString()でRFC3339_EXTENDED形式の文字列が取得できること
     */
    public function test__toString(): void
    {
        $dateTime = new DateTimeImmutable('2024-12-31 00:00:00.000+09:00');
        $disbandDate = new DisbandDate($dateTime);

        $this->assertSame($dateTime->format(DateTimeImmutable::RFC3339_EXTENDED), (string) $disbandDate);
    }

    /**
     * 正常系: format()で指定したフォーマットで文字列が取得できること
     */
    public function testFormat(): void
    {
        $dateTime = new DateTimeImmutable('2024-12-31 00:00:00');
        $disbandDate = new DisbandDate($dateTime);

        $this->assertSame('2024-12-31', $disbandDate->format('Y-m-d'));
        $this->assertSame('2024年12月31日', $disbandDate->format('Y年m月d日'));
    }

    /**
     * 正常系: isPastDate()で過去日判定が正しく動作すること
     */
    public function testIsPastDate(): void
    {
        $disbandDate = new DisbandDate(new DateTimeImmutable('2024-12-31 00:00:00'));

        $futureDate = new DateTimeImmutable('2025-12-31 00:00:00');
        $pastDate = new DateTimeImmutable('2023-12-31 00:00:00');
        $sameDate = new DateTimeImmutable('2024-12-31 00:00:00');

        $this->assertTrue($disbandDate->isPastDate($futureDate));
        $this->assertFalse($disbandDate->isPastDate($pastDate));
        $this->assertFalse($disbandDate->isPastDate($sameDate));
    }

    /**
     * 正常系: isFutureDate()で未来日判定が正しく動作すること
     */
    public function testIsFutureDate(): void
    {
        $disbandDate = new DisbandDate(new DateTimeImmutable('2024-12-31 00:00:00'));

        $futureDate = new DateTimeImmutable('2025-12-31 00:00:00');
        $pastDate = new DateTimeImmutable('2023-12-31 00:00:00');
        $sameDate = new DateTimeImmutable('2024-12-31 00:00:00');

        $this->assertFalse($disbandDate->isFutureDate($futureDate));
        $this->assertTrue($disbandDate->isFutureDate($pastDate));
        $this->assertFalse($disbandDate->isFutureDate($sameDate));
    }
}
