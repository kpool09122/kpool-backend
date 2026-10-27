<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Service;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\VideoLink\Application\Exception\VideoMetadataUnavailableException;
use Source\Wiki\VideoLink\Infrastructure\Service\PublicVideoHostResolverInterface;
use Source\Wiki\VideoLink\Infrastructure\Service\VideoMetadataService;
use Tests\TestCase;

class VideoMetadataServiceTest extends TestCase
{
    public function testRejectsSuccessfulShortUrlResponseWithoutAResolvedVideoUrl(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://vm.tiktok.com/*' => Http::response($this->page())]);
        /** @var ImageServiceInterface&Mockery\MockInterface $images */
        $images = Mockery::mock(ImageServiceInterface::class);
        $images->shouldNotReceive('upload');
        $this->expectException(InvalidArgumentException::class);
        $this->service($images)->resolve('https://vm.tiktok.com/abc/');
    }

    /**
     * @param positive-int $width
     * @param positive-int $height
     */
    #[DataProvider('images')]
    public function testDecodesAndStoresPublicCoverWithoutPersistingSignedUrl(string $format, int $width, int $height): void
    {
        Storage::fake('public');
        config()->set('filesystems.image_disk', 'public');
        $image = imagecreatetruecolor($width, $height);
        self::assertNotFalse($image);
        ob_start();
        if ($format === 'png') {
            imagepng($image);
        } else {
            imagejpeg($image);
        }
        $bytes = ob_get_clean();
        self::assertIsString($bytes);
        Http::preventStrayRequests();
        Http::fake([
            'https://www.tiktok.com/*' => Http::response($this->page()),
            'https://p16.tiktokcdn.com/*' => Http::response($bytes),
        ]);
        $metadata = $this->service()->resolve('https://www.tiktok.com/@scout2015/video/6718335390845095173');
        Storage::disk('public')->assertExists((string) $metadata->thumbnailPath);
        self::assertMatchesRegularExpression('~^images/[^/]+\.webp$~', (string) $metadata->thumbnailPath);
        self::assertSame(12, $metadata->durationSeconds->value());
        $storedBytes = Storage::disk('public')->get((string) $metadata->thumbnailPath);
        self::assertIsString($storedBytes);
        $saved = getimagesizefromstring($storedBytes);
        self::assertNotFalse($saved);
        self::assertSame(IMAGETYPE_WEBP, $saved[2]);
        self::assertLessThanOrEqual(1024, max($saved[0], $saved[1]));
        self::assertEqualsWithDelta($width / $height, $saved[0] / $saved[1], 0.01);
    }

    /** @return list<array{string,int,int}> */
    public static function images(): array
    {
        return [['jpeg', 486, 648], ['png', 1080, 1920], ['png', 640, 360]];
    }

    #[DataProvider('unavailablePages')]
    public function testDoesNotFetchOrStoreAnImageForUnusablePages(string $body, int $status): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://www.tiktok.com/*' => Http::response($body, $status)]);
        /** @var ImageServiceInterface&Mockery\MockInterface $images */
        $images = Mockery::mock(ImageServiceInterface::class);
        $images->shouldNotReceive('upload');
        $this->expectException(VideoMetadataUnavailableException::class);

        try {
            $this->service($images)->resolve('https://www.tiktok.com/@scout2015/video/6718335390845095173');
        } finally {
            Http::assertSentCount(1);
        }
    }

    /** @return array<string,array{string,int}> */
    public static function unavailablePages(): array
    {
        return [
            'restricted' => ['Access denied', 403],
            'deleted' => ['', 404],
            'no hydration data' => ['<html>changed</html>', 200],
            'invalid JSON' => ['<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__">{</script>', 200],
            'changed scope' => ['<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__">{}</script>', 200],
            'non-object JSON' => ['<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__">null</script>', 200],
            'too large' => [str_repeat('x', 4_194_305), 200],
        ];
    }

    #[DataProvider('badCovers')]
    public function testRejectsUnsafeOrDamagedCovers(string $cover, string $bytes): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://www.tiktok.com/*' => Http::response($this->page($cover)),
            'https://p16.tiktokcdn.com/*' => Http::response($bytes),
        ]);
        $this->expectException(VideoMetadataUnavailableException::class);
        $this->service()->resolve('https://www.tiktok.com/@scout2015/video/6718335390845095173');
    }

    /** @return array<string,array{string,string}> */
    public static function badCovers(): array
    {
        return [
            'private IP' => ['https://127.0.0.1/cover', ''],
            'lookalike host' => ['https://tiktokcdn.com.attacker.example/cover', ''],
            'plain HTTP' => ['http://p16.tiktokcdn.com/cover', ''],
            'userinfo' => ['https://user@p16.tiktokcdn.com/cover', ''],
            'port' => ['https://p16.tiktokcdn.com:8443/cover', ''],
            'broken image' => ['https://p16.tiktokcdn.com/cover', 'not an image'],
            'oversized image' => ['https://p16.tiktokcdn.com/cover', str_repeat('x', 8_388_609)],
        ];
    }

    public function testStopsImageRedirectBeforeItReachesInternalNetwork(): void
    {
        Http::fake([
            'https://www.tiktok.com/*' => Http::response($this->page()),
            'https://p16.tiktokcdn.com/*' => Http::response('', 302, ['Location' => 'https://169.254.169.254/secret']),
        ]);
        $this->expectException(VideoMetadataUnavailableException::class);

        try {
            $this->service()->resolve('https://www.tiktok.com/@scout2015/video/6718335390845095173');
        } finally {
            Http::assertSentCount(2);
        }
    }

    public function testRejectsReboundPrivateAddressBeforeSending(): void
    {
        Http::preventStrayRequests();
        /** @var PublicVideoHostResolverInterface&Mockery\MockInterface $resolver */
        $resolver = Mockery::mock(PublicVideoHostResolverInterface::class);
        $resolver->shouldReceive('resolve')->once()->andReturn('127.0.0.1');
        $this->expectException(VideoMetadataUnavailableException::class);

        try {
            (new VideoMetadataService($resolver, $this->app()->make(ImageServiceInterface::class)))->resolve('https://vt.tiktok.com/abc/');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function testLimitsRedirectsAndHandlesNetworkTimeouts(): void
    {
        Http::fake(['https://vt.tiktok.com/*' => Http::response('', 302, ['Location' => 'https://vt.tiktok.com/loop/'])]);

        try {
            $this->service()->resolve('https://vt.tiktok.com/loop/');
            self::fail('A redirect loop must fail.');
        } catch (VideoMetadataUnavailableException) {
            Http::assertSentCount(4);
        }
        Http::fake(['*' => Http::failedConnection()]);
        $this->expectException(VideoMetadataUnavailableException::class);
        $this->service()->resolve('https://vt.tiktok.com/timeout/');
    }

    public function testStorageFailureIsSanitized(): void
    {
        Http::fake([
            'https://www.tiktok.com/*' => Http::response($this->page()),
            'https://p16.tiktokcdn.com/*' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jA0sAAAAASUVORK5CYII=')),
        ]);
        /** @var ImageServiceInterface&Mockery\MockInterface $images */
        $images = Mockery::mock(ImageServiceInterface::class);
        $images->shouldReceive('upload')->once()->andThrow(new RuntimeException('signed CDN secret'));

        try {
            $this->service($images)->resolve('https://www.tiktok.com/@scout2015/video/6718335390845095173');
            self::fail('Storage failure cannot be success.');
        } catch (VideoMetadataUnavailableException $e) {
            self::assertNull($e->getPrevious());
            self::assertStringNotContainsString('secret', (string) $e);
        }
    }

    public function testPinsVerifiedAddressAndAbortsChunkedOversizedDownload(): void
    {
        Http::fake(function (Request $request, array $options) {
            self::assertFalse($options['allow_redirects']);
            self::assertSame('', $options['proxy']);
            self::assertSame(8, $options['timeout']);
            $curl = $options['curl'];
            self::assertIsArray($curl);
            self::assertSame(['www.tiktok.com:443:8.8.8.8'], $curl[CURLOPT_RESOLVE]);
            $write = $curl[CURLOPT_WRITEFUNCTION];
            self::assertIsCallable($write);
            $handle = curl_init();
            self::assertSame(0, $write($handle, str_repeat('x', 4_194_305)));

            return Http::response('missing public metadata');
        });
        $this->expectException(VideoMetadataUnavailableException::class);
        $this->service()->resolve('https://www.tiktok.com/@scout2015/video/6718335390845095173');
    }

    private function service(?ImageServiceInterface $images = null): VideoMetadataService
    {
        /** @var PublicVideoHostResolverInterface&Mockery\MockInterface $resolver */
        $resolver = Mockery::mock(PublicVideoHostResolverInterface::class);
        $resolver->shouldReceive('resolve')->andReturn('8.8.8.8');

        return new VideoMetadataService($resolver, $images ?? $this->app()->make(ImageServiceInterface::class));
    }

    private function page(string $cover = 'https://p16.tiktokcdn.com/cover?signature=not-persisted'): string
    {
        return '<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__">' . json_encode([
            '__DEFAULT_SCOPE__' => [
                'webapp.video-detail' => ['itemInfo' => ['itemStruct' => ['id' => '6718335390845095173', 'video' => ['cover' => $cover, 'duration' => 12]]]],
                'recommendations' => [['id' => '6910995345421962498', 'video' => ['cover' => 'https://evil.example/recommended', 'duration' => 99]]],
            ],
        ], JSON_THROW_ON_ERROR) . '</script>';
    }

    public function testResolvesSharedUrlAndOnlyTheRequestedVideoAndStoresImage(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://vm.tiktok.com/abc/' => Http::response('', 302, ['Location' => 'https://www.tiktok.com/@scout2015/video/6718335390845095173']),
            'https://www.tiktok.com/*' => Http::response('<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__" type="application/json">' . json_encode(['__DEFAULT_SCOPE__' => ['webapp.video-detail' => ['itemInfo' => ['itemStruct' => ['id' => '6718335390845095173', 'video' => ['cover' => 'https://p16.tiktokcdn.com/image.jpeg?sig=secret', 'duration' => 12]]]]]]) . '</script>'),
            'https://p16.tiktokcdn.com/*' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jA0sAAAAASUVORK5CYII=')),
        ]);
        /** @var PublicVideoHostResolverInterface&Mockery\MockInterface $resolver */
        $resolver = Mockery::mock(PublicVideoHostResolverInterface::class);
        $resolver->shouldReceive('resolve')->andReturn('8.8.8.8');
        /** @var ImageServiceInterface&Mockery\MockInterface $imageService */
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')->once()->andReturn(new ImagePath('images/cover.webp'));
        $service = new VideoMetadataService($resolver, $imageService);
        $metadata = $service->resolve('https://vm.tiktok.com/abc/');
        self::assertSame('6718335390845095173', $metadata->url->externalVideoId());
        self::assertSame(12, $metadata->durationSeconds->value());
        self::assertSame('images/cover.webp', (string) $metadata->thumbnailPath);
        Http::assertSentCount(3);
    }

    public function testRejectsUnsafeRedirectWithoutFetchingIt(): void
    {
        Http::fake(['https://vt.tiktok.com/abc/' => Http::response('', 302, ['Location' => 'http://127.0.0.1/secret'])]);
        /** @var PublicVideoHostResolverInterface&Mockery\MockInterface $resolver */
        $resolver = Mockery::mock(PublicVideoHostResolverInterface::class);
        $resolver->shouldReceive('resolve')->once()->andReturn('8.8.8.8');
        $service = new VideoMetadataService($resolver, $this->app()->make(ImageServiceInterface::class));
        $this->expectException(InvalidArgumentException::class);

        try {
            $service->resolve('https://vt.tiktok.com/abc/');
        } finally {
            Http::assertSentCount(1);
        }
    }

    public function testRejectsMismatchedVideoRatherThanUsingRecommendation(): void
    {
        Http::fake(['https://www.tiktok.com/*' => Http::response('<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__">{"__DEFAULT_SCOPE__":{"webapp.video-detail":{"itemInfo":{"itemStruct":{"id":"6910995345421962498","video":{"cover":"https://p16.tiktokcdn.com/other.jpeg","duration":12}}}}}}</script>')]);
        /** @var PublicVideoHostResolverInterface&Mockery\MockInterface $resolver */
        $resolver = Mockery::mock(PublicVideoHostResolverInterface::class);
        $resolver->shouldReceive('resolve')->once()->andReturn('8.8.8.8');
        $this->expectException(VideoMetadataUnavailableException::class);
        (new VideoMetadataService($resolver, $this->app()->make(ImageServiceInterface::class)))->resolve('https://www.tiktok.com/@scout2015/video/6718335390845095173');
    }
}
