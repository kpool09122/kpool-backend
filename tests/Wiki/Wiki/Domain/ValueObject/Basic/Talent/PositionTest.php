<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Talent;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Position;

class PositionTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $position = new Position('Main Vocalist');
        $this->assertSame('Main Vocalist', $position->value());
    }

    /**
     * 正常系: 空文字でインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithEmptyString(): void
    {
        $position = new Position('');
        $this->assertSame('', $position->value());
    }

    /**
     * 正常系: 64文字でインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithMaxLength(): void
    {
        $value = str_repeat('a', 64);
        $position = new Position($value);
        $this->assertSame($value, $position->value());
    }

    /**
     * 異常系: 65文字以上で例外がスローされること
     *
     * @return void
     */
    public function testThrowsInvalidArgumentExceptionWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Position must be 64 characters or less.');
        new Position(str_repeat('a', 65));
    }
}
