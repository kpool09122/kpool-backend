<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\Service;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\VideoLink\Application\Service\VideoMetadata;

class VideoMetadataTest extends TestCase
{
    public function testRetainsExactResolvedValues(): void
    {
        $url = new TikTokVideoUrl('https://www.tiktok.com/@scout2015/video/6718335390845095173');
        $duration = new DurationSeconds(12);
        $path = new ImagePath('images/cover.webp');
        $metadata = new VideoMetadata($url, $duration, $path);
        self::assertSame($url, $metadata->url);
        self::assertSame($duration, $metadata->durationSeconds);
        self::assertSame($path, $metadata->thumbnailPath);
    }

    public function testRejectsUnresolvedTikTokUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VideoMetadata(new TikTokVideoUrl('https://vm.tiktok.com/abc/'), new DurationSeconds(12), new ImagePath('images/cover.webp'));
    }
}
