<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Service;

use Application\Mail\VideoWatchRequestDigestMail;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class VideoWatchRequestDigestMailTest extends TestCase
{
    /** @return iterable<string, array{Language, string, string}> */
    public static function languages(): iterable
    {
        yield 'ja' => [Language::JAPANESE, 'これみてリクエストが届いています', '削除済みの動画'];
        yield 'en' => [Language::ENGLISH, 'Your video watch request digest', 'Deleted video'];
        yield 'ko' => [Language::KOREAN, '시청 요청이 도착했습니다', '삭제된 동영상'];
    }

    #[DataProvider('languages')]
    public function testLocalizedHtmlTextWithCountsThumbnailsAndVideoTabLinks(Language $language, string $subject, string $deleted): void
    {
        $this->app()->make('view')->addLocation(dirname(__DIR__, 5) . '/resources/views');
        $url = 'https://k-pool.test/ko/wiki/tl-example?tab=video';
        $thumbnail = 'https://i.ytimg.com/vi/abcdefghijk/hqdefault.jpg';
        $mail = new VideoWatchRequestDigestMail([
            ['wikiTitle' => '<script>alert(1)</script>', 'videoTitle' => 'Video & title', 'requestCount' => 2, 'likeCount' => 12, 'thumbnailUrl' => $thumbnail],
            ['wikiTitle' => 'Wiki', 'videoTitle' => null, 'requestCount' => 1, 'likeCount' => 0, 'thumbnailUrl' => null],
        ], 123, 45, $url, $language);
        self::assertSame($subject, $mail->envelope()->subject);
        self::assertSame($language->value, $mail->locale);
        $html = $mail->render();
        self::assertStringContainsString($url, $html);
        self::assertStringContainsString($deleted, $html);
        self::assertStringContainsString('src="' . $thumbnail . '"', $html);
        self::assertStringContainsString('123', $html);
        self::assertStringContainsString('45', $html);
        self::assertStringNotContainsString('/admin/wiki/watch-requests', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        $content = $mail->content();
        self::assertNotNull($content->text);
        $text = view('emails.wiki.text.video-watch-digest', ['videos' => $mail->videos, 'recentRequestCount' => $mail->recentRequestCount, 'newRequestCount' => $mail->newRequestCount, 'videosUrl' => $mail->videosUrl, 'copy' => $content->with['copy']])->render();
        self::assertStringContainsString($url, $text);
        self::assertStringContainsString($deleted, $text);
        self::assertStringContainsString('123', $text);
        self::assertStringContainsString('45', $text);
    }
}
