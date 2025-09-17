<?php

namespace Tests\Wiki\Agency\Domain\ValueObject;

use Businesses\Wiki\Agency\Domain\ValueObject\CEO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Helper\StrTestHelper;

class CEOTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = 'J.Y. Park';
        $CEO = new CEO($name);
        $this->assertSame($name, (string)$CEO);
    }

    /**
     * 正常系：空文字の場合、例外がスローされないこと.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $name = '';
        $CEO = new CEO($name);
        $this->assertSame($name, (string)$CEO);
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CEO(StrTestHelper::generateStr(CEO::MAX_LENGTH + 1));
    }
}
