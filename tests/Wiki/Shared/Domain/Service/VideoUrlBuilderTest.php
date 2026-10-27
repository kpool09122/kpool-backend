<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\Service;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\Service\VideoUrlBuilder;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;

class VideoUrlBuilderTest extends TestCase
{
    public function testNormalizesProvidersWithoutLosingStringIdentifiers(): void
    {
        $video = (new VideoUrlBuilder())->build('https://www.tiktok.com/@scout2015/video/6718335390845095173?is_from_webapp=1');
        self::assertInstanceOf(TikTokVideoUrl::class, $video);
        self::assertSame('tiktok', $video->provider());
        self::assertSame('6718335390845095173', $video->externalVideoId());
        self::assertSame('https://www.tiktok.com/@scout2015/video/6718335390845095173', (string) $video);
        $youtube = (new VideoUrlBuilder())->build('https://youtu.be/Os_heh8vPfs?t=30');
        self::assertInstanceOf(YouTubeVideoUrl::class, $youtube);
        self::assertSame('youtube', $youtube->provider());
        self::assertSame('Os_heh8vPfs', $youtube->externalVideoId());
        self::assertSame('https://www.youtube.com/watch?v=Os_heh8vPfs', (string) $youtube);
    }

    #[DataProvider('shortUrls')]
    public function testAcceptsShortUrlsForMetadataResolution(string $input): void
    {
        $url = (new VideoUrlBuilder())->build($input);
        self::assertInstanceOf(TikTokVideoUrl::class, $url);
        self::assertSame($input, (string) $url);
        self::assertNull($url->externalVideoId());
    }

    /** @return list<array{string}> */
    public static function shortUrls(): array
    {
        return [['https://vm.tiktok.com/abc/'], ['https://vt.tiktok.com/abc/']];
    }

    #[DataProvider('invalidUrls')]
    public function testRejectsNonVideoAndUnsafeUrls(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new VideoUrlBuilder())->build($url);
    }

    /** @return list<array{string}> */
    public static function invalidUrls(): array
    {
        return array_map(static fn (string $url): array => [$url], [
            'https://www.tiktok.com/@scout2015',
            'https://www.tiktok.com/@scout2015/photo/6718335390845095173',
            'https://www.tiktok.com.evil.test/@scout2015/video/6718335390845095173',
            'https://user@www.tiktok.com/@scout2015/video/6718335390845095173',
            'http://www.tiktok.com/@scout2015/video/6718335390845095173',
            'https://www.tiktok.com:444/@scout2015/video/6718335390845095173',
            'https://www.tiktok.com/@scout2015/video/invalid',
            'https://www.tiktok.com/@scout2015/video/6718335390845095173/extra',
            'https://127.0.0.1/video/6718335390845095173',
        ]);
    }
}
