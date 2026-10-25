<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;

class YouTubeVideoUrlTest extends TestCase
{
    #[DataProvider('supportedUrls')]
    public function testNormalizesSupportedUrls(string $url): void
    {
        self::assertSame('https://www.youtube.com/watch?v=Os_heh8vPfs', (string) new YouTubeVideoUrl($url));
    }

    /** @return list<array{string}> */
    public static function supportedUrls(): array
    {
        return [
            ['https://www.youtube.com/watch?v=Os_heh8vPfs&list=ignored'],
            ['https://youtube.com/watch?list=ignored&v=Os_heh8vPfs'],
            ['https://youtu.be/Os_heh8vPfs?t=30'],
            ['https://youtu.be/Os_heh8vPfs/'],
            ['https://m.youtube.com/watch?v=Os_heh8vPfs'],
            ['https://www.youtube.com/shorts/Os_heh8vPfs'],
            ['https://www.youtube.com/live/Os_heh8vPfs/'],
            ['https://www.youtube.com/embed/Os_heh8vPfs'],
            ['https://www.youtube-nocookie.com/embed/Os_heh8vPfs'],
            ['https://youtube-nocookie.com/embed/Os_heh8vPfs'],
            ['https://www.youtube.com:443/watch?v=Os_heh8vPfs'],
            ['https://www.youtube.com/watch?v=Os_heh8vPfs&v=ignored'],
        ];
    }

    #[DataProvider('unsupportedUrls')]
    public function testRejectsOtherProvidersAndInvalidVideoUrls(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);
        new YouTubeVideoUrl($url);
    }

    /** @return list<array{string}> */
    public static function unsupportedUrls(): array
    {
        return [
            ['https://www.tiktok.com/@example/video/6718335390845095173'],
            ['https://vimeo.com/123456789'],
            ['https://example.com/watch?v=Os_heh8vPfs'],
            ['https://youtube.com.example.com/watch?v=Os_heh8vPfs'],
            ['https://example.com/?url=https://www.youtube.com/watch?v=Os_heh8vPfs'],
            ['http://www.youtube.com/watch?v=Os_heh8vPfs'],
            ['https://user:pass@www.youtube.com/watch?v=Os_heh8vPfs'],
            ['https://www.youtube.com:444/watch?v=Os_heh8vPfs'],
            ['https://www.youtube.com/@example'],
            ['https://www.youtube.com/playlist?list=Os_heh8vPfs'],
            ['https://www.youtube.com/watch'],
            ['https://www.youtube.com/watch?v=invalid'],
            ['https://www.youtube.com/watch?v=invalid&v=Os_heh8vPfs'],
            ['https://www.youtube.com/watch?v[]=Os_heh8vPfs'],
            ['https://youtu.be/Os_heh8vPfs/extra'],
            ['https://www.youtube.com/shorts/Os_heh8vPfs/extra'],
            ['Os_heh8vPfs'],
            ['javascript:alert(1)'],
        ];
    }
}
