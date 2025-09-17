<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Domain\ValueObject;

use Businesses\Wiki\Member\Domain\ValueObject\Birthday;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

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
