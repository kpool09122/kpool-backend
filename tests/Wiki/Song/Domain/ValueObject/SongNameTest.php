<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;

class SongNameTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = 'TT';
        $songName = new SongName($name);
        $this->assertSame($name, (string)$songName);
    }

    /**
     * 異常系：空文字が渡された場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SongName('');
    }

    /**
     * 異常系：空白が渡された場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenOnlySpace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SongName('    ');
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SongName(StrTestHelper::generateStr(SongName::MAX_LENGTH + 1));
    }
}
