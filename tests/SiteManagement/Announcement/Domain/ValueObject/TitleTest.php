<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Domain\ValueObject;

use Businesses\SiteManagement\Announcement\Domain\ValueObject\Title;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Helper\StrTestHelper;

class TitleTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $text = '🏆 あなたの一票が推しを輝かせる！新機能「グローバル投票」スタート！';
        $content = new Title($text);
        $this->assertSame($text, (string)$content);
    }

    /**
     * 異常系：空文字の場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Title('');
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Title(StrTestHelper::generateStr(Title::MAX_LENGTH + 1));
    }
}
