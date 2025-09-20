<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject\Foundation;

use DateTimeImmutable;
use DateTimeInterface;
use Source\Shared\Domain\ValueObject\Foundation\DateTimeBaseValue;
use Tests\TestCase;

class DateTimeBaseValueTest extends TestCase
{
    /** @phpstan-ignore-next-line */
    private DateTimeImmutable $dateTimeImmutable;

    /** @phpstan-ignore-next-line */
    private DateTimeBaseValue $dateTimeBaseValue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dateTimeImmutable = new DateTimeImmutable();
        $this->dateTimeBaseValue = new class ($this->dateTimeImmutable) extends DateTimeBaseValue {
            public function __construct(DateTimeImmutable $value)
            {
                parent::__construct($value);
            }

            protected function validate(DateTimeImmutable $value): void
            {
                // 検証ロジックなし（テスト用）
            }
        };
    }

    /**
     * __toStringメソッドが正しく動作すること.
     *
     * @return void
     */
    public function test__toString(): void
    {
        $this->assertInstanceOf(DateTimeBaseValue::class, $this->dateTimeBaseValue);
        $expectedDateTime = $this->dateTimeImmutable->format(DateTimeInterface::RFC3339_EXTENDED);
        $this->assertSame($expectedDateTime, (string)$this->dateTimeBaseValue);
    }

    /**
     * isPastDateメソッドが渡される引数の値より過去かどうかによって真偽値が変化すること.
     *
     * @return void
     */
    public function testIsPastDate(): void
    {
        $futureDate = new DateTimeImmutable('+1 day');
        $this->assertTrue($this->dateTimeBaseValue->isPastDate($futureDate));

        $sameDate = $this->dateTimeImmutable;
        $this->assertFalse($this->dateTimeBaseValue->isFutureDate($sameDate));

        $pastDate = new DateTimeImmutable('-1 day');
        $this->assertFalse($this->dateTimeBaseValue->isPastDate($pastDate));
    }

    /**
     * isFutureDateメソッドが渡される引数の値より未来かどうかによって真偽値が変化すること.
     *
     * @return void
     */
    public function testIsFutureDate(): void
    {
        $futureDate = new DateTimeImmutable('+1 day');
        $this->assertFalse($this->dateTimeBaseValue->isFutureDate($futureDate));

        $sameDate = $this->dateTimeImmutable;
        $this->assertFalse($this->dateTimeBaseValue->isFutureDate($sameDate));

        $pastDate = new DateTimeImmutable('-1 day');
        $this->assertTrue($this->dateTimeBaseValue->isFutureDate($pastDate));
    }

    /**
     * constructorで渡されたDateTimeImmutableが取得できること.
     *
     * @return void
     */
    public function testValue(): void
    {
        $this->assertSame($this->dateTimeImmutable, $this->dateTimeBaseValue->value());
    }

    /**
     * 引数で指定したフォーマットに正しく変換されること.
     *
     * @return void
     */
    public function testFormat(): void
    {
        $format = 'Y-m-d';
        $expected = $this->dateTimeBaseValue->value()->format($format);
        $this->assertSame($expected, $this->dateTimeBaseValue->format($format));
    }
}
