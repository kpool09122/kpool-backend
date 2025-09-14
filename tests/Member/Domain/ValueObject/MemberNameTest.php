<?php

namespace Tests\Member\Domain\ValueObject;

use Businesses\Member\Domain\ValueObject\MemberName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Helper\StrTestHelper;

class MemberNameTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = '채영';
        $memberName = new MemberName($name);
        $this->assertSame($name, (string)$memberName);
    }

    /**
     * 異常系：空文字が渡された場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MemberName('');
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MemberName(StrTestHelper::generateStr(MemberName::MAX_LENGTH + 1));
    }
}
