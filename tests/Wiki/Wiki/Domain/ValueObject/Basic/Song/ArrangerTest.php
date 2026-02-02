<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Song;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Arranger;
use Tests\Helper\StrTestHelper;

class ArrangerTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = 'Teddy Park';
        $arranger = new Arranger($name);
        $this->assertSame($name, (string)$arranger);
    }

    /**
     * 正常系：空文字が渡された場合、空文字が返却されること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $name = '';
        $arranger = new Arranger($name);
        $this->assertSame($name, (string)$arranger);
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Arranger(StrTestHelper::generateStr(Arranger::MAX_LENGTH + 1));
    }
}
