<?php

namespace Tests\Shared\ValueObject;

use Businesses\Shared\ValueObject\ExternalContentLink;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ExternalContentLinkTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $link = 'https://example.youtube.com/watch?v=dQw4w9WgXcQ';
        $externalContentLink = new ExternalContentLink($link);
        $this->assertSame($link, (string)$externalContentLink);
    }

    /**
     * 異常系：httpsから始まらない場合、例外がスローされること.
     *
     * @return void
     */
    public function testNotStartWithHttps(): void
    {
        $link = 'http://example.youtube.com/watch?v=dQw4w9WgXcQ';
        $this->expectException(InvalidArgumentException::class);
        new ExternalContentLink($link);
    }

    /**
     * 異常系：URL形式でない場合、例外がスローされること.
     *
     * @return void
     */
    public function testNotURLType(): void
    {
        $link = 'https://';
        $this->expectException(InvalidArgumentException::class);
        new ExternalContentLink($link);

        $link = 'https://example.com:abc';
        $this->expectException(InvalidArgumentException::class);
        new ExternalContentLink($link);

        $link = 'https://example_underbar.com:abc';
        $this->expectException(InvalidArgumentException::class);
        new ExternalContentLink($link);

        $link = 'https://192.168.0.256';
        $this->expectException(InvalidArgumentException::class);
        new ExternalContentLink($link);

        $link = 'https://user:@example.com';
        $this->expectException(InvalidArgumentException::class);
        new ExternalContentLink($link);

        $link = 'https:// example.com';
        $this->expectException(InvalidArgumentException::class);
        new ExternalContentLink($link);
    }
}
