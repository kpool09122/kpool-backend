<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;

class TikTokVideoUrlTest extends TestCase
{
    #[DataProvider('validUrls')]
    public function testAcceptsAndNormalizesVideoUrls(string $input, string $normalized, ?string $videoId): void
    {
        $url = new TikTokVideoUrl($input);
        self::assertSame($normalized, (string) $url);
        self::assertSame('tiktok', $url->provider());
        self::assertSame($videoId, $url->externalVideoId());
        self::assertTrue(TikTokVideoUrl::hasSupportedHost($input));
    }

    /** @return list<array{string, string, ?string}> */
    public static function validUrls(): array
    {
        return [
            [' https://TIKTOK.COM:443/@scout2015/video/6718335390845095173/?tracking=1 ', 'https://www.tiktok.com/@scout2015/video/6718335390845095173', '6718335390845095173'],
            ['https://www.tiktok.com/@a_b.c/video/100000000000000', 'https://www.tiktok.com/@a_b.c/video/100000000000000', '100000000000000'],
            ['https://www.tiktok.com/@' . str_repeat('a', 24) . '/video/12345678901234567890', 'https://www.tiktok.com/@' . str_repeat('a', 24) . '/video/12345678901234567890', '12345678901234567890'],
            ['https://VM.TIKTOK.COM/Ab123/?tracking=1', 'https://vm.tiktok.com/Ab123/?tracking=1', null],
            ['https://vt.tiktok.com/Ab123', 'https://vt.tiktok.com/Ab123', null],
        ];
    }

    #[DataProvider('invalidUrls')]
    public function testRejectsInvalidVideoUrls(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TikTokVideoUrl($url);
    }

    /** @return list<array{string}> */
    public static function invalidUrls(): array
    {
        return array_map(static fn (string $url): array => [$url], [
            'http://www.tiktok.com/@user/video/6718335390845095173',
            'https://user:pass@www.tiktok.com/@user/video/6718335390845095173',
            'https://www.tiktok.com:444/@user/video/6718335390845095173',
            'https://www.tiktok.com/@user/video/6718335390845095173#fragment',
            'https://www.tiktok.com.evil.test/@user/video/6718335390845095173',
            'https://www.tiktok.com/@user',
            'https://www.tiktok.com/@user/photo/6718335390845095173',
            'https://www.tiktok.com/@' . str_repeat('a', 25) . '/video/6718335390845095173',
            'https://www.tiktok.com/@user/video/012345678901234',
            'https://www.tiktok.com/@user/video/12345678901234',
            'https://www.tiktok.com/@user/video/123456789012345678901',
            'https://www.tiktok.com/@user/video/6718335390845095173/extra',
            'https://vm.tiktok.com/',
            'https://vt.tiktok.com/abc/extra',
            'https://vt.tiktok.com/abc_def',
            'https://vt.tiktok.com/abc/#fragment',
            'https://youtu.be/Os_heh8vPfs',
            'not a URL',
        ]);
    }

    public function testHostDetectionDoesNotAcceptLookalikesOrUserinfo(): void
    {
        self::assertFalse(TikTokVideoUrl::hasSupportedHost('https://www.tiktok.com.evil.test/video'));
        self::assertFalse(TikTokVideoUrl::hasSupportedHost('https://tiktok.com@evil.test/video'));
    }
}
