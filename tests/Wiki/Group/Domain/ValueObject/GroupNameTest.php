<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Tests\Helper\StrTestHelper;

class GroupNameTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = 'TWICE';
        $groupName = new GroupName($name);
        $this->assertSame($name, (string)$groupName);
    }

    /**
     * 異常系：空文字が渡された場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GroupName('');
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GroupName(StrTestHelper::generateStr(GroupName::MAX_LENGTH + 1));
    }
}
