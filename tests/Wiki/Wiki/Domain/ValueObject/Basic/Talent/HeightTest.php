<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Talent;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Height;

class HeightTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $height = new Height(170);
        $this->assertSame(170, $height->centimeters());
    }

    /**
     * 異常系: 0が渡された場合、例外がスローされること
     *
     * @return void
     */
    public function testThrowsInvalidArgumentExceptionWhenZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Height must be positive.');
        new Height(0);
    }

    /**
     * 異常系: 負の値が渡された場合、例外がスローされること
     *
     * @return void
     */
    public function testThrowsInvalidArgumentExceptionWhenNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Height must be positive.');
        new Height(-1);
    }
}
