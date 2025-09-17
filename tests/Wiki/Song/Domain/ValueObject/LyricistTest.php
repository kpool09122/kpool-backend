<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\ValueObject;

use Businesses\Wiki\Song\Domain\ValueObject\Lyricist;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Helper\StrTestHelper;

class LyricistTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = 'Sam Lewis';
        $lyricist = new Lyricist($name);
        $this->assertSame($name, (string)$lyricist);
    }

    /**
     * 異常系：空文字が渡された場合、空文字が返却されること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $name = '';
        $lyricist = new Lyricist($name);
        $this->assertSame($name, (string)$lyricist);
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Lyricist(StrTestHelper::generateStr(Lyricist::MAX_LENGTH + 1));
    }
}
