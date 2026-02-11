<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Shared;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;

class RepresentativeSymbolTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $symbol = new RepresentativeSymbol('strawberry');
        $this->assertSame('strawberry', $symbol->value());
    }

    /**
     * 正常系: 空文字でインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithEmptyString(): void
    {
        $symbol = new RepresentativeSymbol('');
        $this->assertSame('', $symbol->value());
    }

    /**
     * 正常系: 32文字でインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithMaxLength(): void
    {
        $value = str_repeat('a', 32);
        $symbol = new RepresentativeSymbol($value);
        $this->assertSame($value, $symbol->value());
    }

    /**
     * 異常系: 33文字以上で例外がスローされること
     *
     * @return void
     */
    public function testThrowsInvalidArgumentExceptionWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Representative symbol must be 32 characters or less.');
        new RepresentativeSymbol(str_repeat('a', 33));
    }
}
