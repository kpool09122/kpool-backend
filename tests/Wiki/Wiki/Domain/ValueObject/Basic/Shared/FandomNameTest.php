<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Shared;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;

class FandomNameTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $fandomName = new FandomName('ARMY');
        $this->assertSame('ARMY', $fandomName->value());
    }

    /**
     * 正常系: 空文字でインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithEmptyString(): void
    {
        $fandomName = new FandomName('');
        $this->assertSame('', $fandomName->value());
    }

    /**
     * 正常系: 64文字でインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithMaxLength(): void
    {
        $value = str_repeat('a', 64);
        $fandomName = new FandomName($value);
        $this->assertSame($value, $fandomName->value());
    }

    /**
     * 異常系: 65文字以上で例外がスローされること
     *
     * @return void
     */
    public function testThrowsInvalidArgumentExceptionWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fandom name must be 64 characters or less.');
        new FandomName(str_repeat('a', 65));
    }
}
