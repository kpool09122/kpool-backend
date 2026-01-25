<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;

class YearMonthTest extends TestCase
{
    /**
     * 正常系: 有効なYYYY-MM形式でインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $yearMonth = new YearMonth('2026-01');
        $this->assertSame('2026-01', (string) $yearMonth);
    }

    /**
     * 異常系: 不正な形式の場合、例外が発生すること
     */
    public function testValidateInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('YearMonth must be in YYYY-MM format.');
        new YearMonth('2026-1');
    }

    /**
     * 異常系: 不正な月の場合、例外が発生すること
     */
    public function testValidateInvalidMonth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('YearMonth must be in YYYY-MM format.');
        new YearMonth('2026-13');
    }

    /**
     * 正常系: fromDateTimeでインスタンスが生成されること
     */
    public function testFromDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2026-03-15');
        $yearMonth = YearMonth::fromDateTime($dateTime);
        $this->assertSame('2026-03', (string) $yearMonth);
    }

    /**
     * 正常系: fromStringでインスタンスが生成されること
     */
    public function testFromString(): void
    {
        $yearMonth = YearMonth::fromString('2026-05');
        $this->assertSame('2026-05', (string) $yearMonth);
    }

    /**
     * 正常系: currentで現在の年月が取得されること
     */
    public function testCurrent(): void
    {
        $expected = (new DateTimeImmutable())->format('Y-m');
        $yearMonth = YearMonth::current();
        $this->assertSame($expected, (string) $yearMonth);
    }

    /**
     * 正常系: yearで年が取得されること
     */
    public function testYear(): void
    {
        $yearMonth = new YearMonth('2026-01');
        $this->assertSame(2026, $yearMonth->year());
    }

    /**
     * 正常系: monthで月が取得されること
     */
    public function testMonth(): void
    {
        $yearMonth = new YearMonth('2026-01');
        $this->assertSame(1, $yearMonth->month());
    }

    /**
     * 正常系: subtractで指定した月数を引いた年月が取得されること
     */
    public function testSubtract(): void
    {
        $yearMonth = new YearMonth('2026-03');
        $result = $yearMonth->subtract(1);
        $this->assertSame('2026-02', (string) $result);
    }

    /**
     * 正常系: subtractで年をまたいで引き算ができること
     */
    public function testSubtractAcrossYear(): void
    {
        $yearMonth = new YearMonth('2026-02');
        $result = $yearMonth->subtract(3);
        $this->assertSame('2025-11', (string) $result);
    }

    /**
     * 正常系: toFirstDayOfMonthで月の初日が取得されること
     */
    public function testToFirstDayOfMonth(): void
    {
        $yearMonth = new YearMonth('2026-03');
        $date = $yearMonth->toFirstDayOfMonth();
        $this->assertSame('2026-03-01 00:00:00', $date->format('Y-m-d H:i:s'));
    }
}
