<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Support;

use Illuminate\Support\Facades\Storage;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Tests\TestCase;

class ImageUrlTest extends TestCase
{
    public function testEmptyPathsReturnNull(): void
    {
        self::assertNull(ImageUrl::fromPath(null));
        self::assertNull(ImageUrl::fromPath(''));
    }

    public function testCloudFrontBaseUrlHandlesTrailingSlash(): void
    {
        foreach (['https://images.example.test', 'https://images.example.test/'] as $url) {
            config(['filesystems.image_disk' => 's3', 'filesystems.disks.s3.region' => 'ap-northeast-1', 'filesystems.disks.s3.bucket' => 'images', 'filesystems.disks.s3.url' => $url]);
            Storage::forgetDisk('s3');
            self::assertSame('https://images.example.test/images/test.webp', ImageUrl::fromPath('images/test.webp'));
        }
    }

    public function testLocalAndLeadingSlashPathsKeepExistingUrlBehavior(): void
    {
        app('url')->forceRootUrl('http://localhost');
        config(['filesystems.image_disk' => 'public', 'filesystems.disks.public.url' => 'http://localhost:8080/storage']);
        self::assertSame('http://127.0.0.1:8080/storage/images/test.webp', ImageUrl::fromPath('images/test.webp'));
        self::assertSame('http://127.0.0.1/legacy/test.webp', ImageUrl::fromPath('/legacy/test.webp'));
        app('url')->forceRootUrl('http://127.0.0.1');
        self::assertSame('http://127.0.0.1/legacy/test.webp', ImageUrl::fromPath('/legacy/test.webp'));
    }
}
