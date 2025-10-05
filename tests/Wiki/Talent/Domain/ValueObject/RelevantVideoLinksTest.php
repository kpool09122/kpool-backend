<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Tests\TestCase;

class RelevantVideoLinksTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること.
     *
     * @return void
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function test__construct(): void
    {
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);
        $this->assertSame($externalContentLinks, $relevantVideoLinks->links());
        $this->assertSame(3, $relevantVideoLinks->count());
        $this->assertSame([(string)$link1, (string)$link2, (string)$link3], $relevantVideoLinks->toStringArray());
    }

    /**
     * 正常系: 空の場合も、例外を出さずに正しく動作すること.
     *
     * @return void
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testWhenEmpty(): void
    {
        $externalContentLink = [];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLink);
        $this->assertSame($externalContentLink, $relevantVideoLinks->links());
        $this->assertSame(0, $relevantVideoLinks->count());
        $this->assertSame([], $relevantVideoLinks->toStringArray());
    }

    /**
     * 異常系: 関連リンクが10件を超える場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenExceedMaxRelevantVideoLinks(): void
    {
        $this->expectException(ExceedMaxRelevantVideoLinksException::class);
        $externalContentLinks = [
            new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ'),
            new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ'),
            new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ'),
            new ExternalContentLink('https://example4.youtube.com/watch?v=dQw4w9WgXcQ'),
            new ExternalContentLink('https://example5.youtube.com/watch?v=dQw4w9WgXcQ'),
            new ExternalContentLink('https://example6.youtube.com/watch?v=dQw4w9WgXcQ'),
            new ExternalContentLink('https://example7.youtube.com/watch?v=dQw4w9WgXcQ'),
            new ExternalContentLink('https://example8.youtube.com/watch?v=dQw4w9WgXcQ'),
            new ExternalContentLink('https://example9.youtube.com/watch?v=dQw4w9WgXcQ'),
            new ExternalContentLink('https://example10.youtube.com/watch?v=dQw4w9WgXcQ'),
            new ExternalContentLink('https://example11.youtube.com/watch?v=dQw4w9WgXcQ'),
        ];
        new RelevantVideoLinks($externalContentLinks);
    }

    /**
     * 正常系: 文字列配列からRelevantVideoLinksを正しく作成できること.
     *
     * @return void
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testFromStringArray(): void
    {
        $array = [
            'https://example.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://example2.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://example3.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://example4.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://example5.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://example6.youtube.com/watch?v=dQw4w9WgXcQ',
        ];
        $relevantVideoLinks = RelevantVideoLinks::formStringArray($array);
        $this->assertSame($array, $relevantVideoLinks->toStringArray());
    }

    /**
     * 正常系: 空配列の場合もfromStringArrayでRelevantVideoLinksを正しく作成できること.
     *
     * @return void
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testFromStringArrayWhenEmpty(): void
    {
        $relevantVideoLinks = RelevantVideoLinks::formStringArray([]);
        $this->assertSame([], $relevantVideoLinks->toStringArray());
    }
}
