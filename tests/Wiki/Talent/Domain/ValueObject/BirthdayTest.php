<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;

class BirthdayTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $date = new DateTimeImmutable('1994-01-01');
        $birthday = new Birthday($date);
        $this->assertSame($date, $birthday->value());
    }

    /**
     * 異常系：未来日付が渡された場合、例外がスローされること.
     *
     * @return void
     */
    public function testThrowsInvalidArgumentException(): void
    {
        $date = new DateTimeImmutable('2099-01-01');
        $this->expectException(InvalidArgumentException::class);
        new Birthday($date);
    }
}
