<?php

namespace Tests\Wiki\Song\Domain\ValueObject;

use Businesses\Wiki\Song\Domain\ValueObject\Composer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Helper\StrTestHelper;

class ComposerTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = '블랙아이드필승';
        $composer = new Composer($name);
        $this->assertSame($name, (string)$composer);
    }

    /**
     * 異常系：空文字が渡された場合、空文字が返却されること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $name = '';
        $composer = new Composer($name);
        $this->assertSame($name, (string)$composer);
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Composer(StrTestHelper::generateStr(Composer::MAX_LENGTH + 1));
    }
}
