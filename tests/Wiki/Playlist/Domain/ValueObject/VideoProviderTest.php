<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use ValueError;

class VideoProviderTest extends TestCase
{
    public function testOnlyYouTubeIsCurrentlySupported(): void
    {
        self::assertSame([VideoProvider::YOUTUBE], VideoProvider::cases());
        self::assertSame('youtube', VideoProvider::YOUTUBE->value);
        $this->expectException(ValueError::class);
        VideoProvider::from('tiktok');
    }
}
