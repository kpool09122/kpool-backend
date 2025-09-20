<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Domain\ValueObject;

use Businesses\Wiki\Member\Domain\ValueObject\RealName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Helper\StrTestHelper;

class RealNameTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = '손채영';
        $realName = new RealName($name);
        $this->assertSame($name, (string)$realName);
    }

    /**
     * 正常系：空文字が渡された場合、例外がスローされないこと.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $name = '';
        $realName = new RealName($name);
        $this->assertSame($name, (string)$realName);
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new RealName(StrTestHelper::generateStr(RealName::MAX_LENGTH + 1));
    }
}
