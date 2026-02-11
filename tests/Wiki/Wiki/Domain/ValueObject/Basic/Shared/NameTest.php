<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Shared;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Tests\Helper\StrTestHelper;

class NameTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $nameStr = 'JYP엔터테인먼트';
        $name = new Name($nameStr);
        $this->assertSame($nameStr, (string)$name);
    }

    /**
     * 異常系：空文字が渡された場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Name('');
    }

    /**
     * 異常系：空白が渡された場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenOnlySpace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Name('    ');
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Name(StrTestHelper::generateStr(Name::MAX_LENGTH + 1));
    }
}
