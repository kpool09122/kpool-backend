<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;

class ReplyContentTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $text = 'お問い合わせありがとうございます。

ご要望いただいた「公式MV一覧をYouTube連携で表示する機能」について、今後の改善候補として検討いたします。
実装可否や時期が決まり次第、サイト内のお知らせ等でご案内いたします。

貴重なご意見をお寄せいただき、ありがとうございました。';
        $content = new ReplyContent($text);
        $this->assertSame($text, (string)$content);
    }

    /**
     * 異常系：空文字の場合、例外がスローされること.
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ReplyContent('');
    }
}
