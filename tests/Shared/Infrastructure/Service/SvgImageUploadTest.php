<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Application\Exception\InvalidRemoteImageException;
use Source\Shared\Application\Service\ImageServiceInterface;
use Tests\TestCase;

class SvgImageUploadTest extends TestCase
{
    private const string SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100"><rect width="100" height="100" fill="#123456"/></svg>';

    /** @return array<string, array{bool}> */
    public static function operations(): array
    {
        return ['upload' => [false], 'import' => [true]];
    }

    #[DataProvider('operations')]
    public function testStoresSvgAsSingleWebpOnConfiguredDisk(bool $import): void
    {
        config(['filesystems.image_disk' => 's3']);
        Storage::fake('s3');
        Storage::fake('public');
        $path = $this->store(self::SVG, $import);

        self::assertMatchesRegularExpression('/^images\/[0-9a-f-]{36}\.webp$/', $path);
        $stored = Storage::disk('s3')->get($path);
        self::assertIsString($stored);
        $size = getimagesizefromstring($stored);
        self::assertNotFalse($size);
        self::assertSame('image/webp', $size['mime']);
        self::assertSame(200, $size[0]);
        self::assertSame(100, $size[1]);
        $image = imagecreatefromstring($stored);
        self::assertNotFalse($image);
        $transparent = imagecolorat($image, 199, 1);
        self::assertNotFalse($transparent);
        self::assertSame(127, imagecolorsforindex($image, $transparent)['alpha']);
        self::assertCount(1, Storage::disk('s3')->allFiles());
        self::assertSame([], Storage::disk('public')->allFiles());
    }

    #[DataProvider('operations')]
    public function testStoresOnlyRasterPixelsForSvgContainingExecutableContentAndRemoteReferences(bool $import): void
    {
        config(['filesystems.image_disk' => 'public']);
        Storage::fake('public');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)" viewBox="0 0 10 10">'
            . '<script>alert(2)</script><foreignObject><div xmlns="http://www.w3.org/1999/xhtml">unsafe</div></foreignObject>'
            . '<image href="https://evil.test/track.png"/>'
            . '<style>rect { fill: url(https://evil.test/fill.svg); }</style>'
            . '<rect width="10" height="10" style="fill: url(https://evil.test/fill.svg)" onclick="alert(3)"/>'
            . '<a href="javascript:alert(4)"><text>label</text></a></svg>';
        $path = $this->store($svg, $import);
        $stored = Storage::disk('public')->get($path);
        self::assertIsString($stored);
        $size = getimagesizefromstring($stored);
        self::assertNotFalse($size);
        self::assertSame('image/webp', $size['mime']);
        self::assertSame(10, $size[0]);
        self::assertSame(10, $size[1]);
        self::assertStringNotContainsString('<svg', $stored);
        self::assertCount(1, Storage::disk('public')->allFiles());
    }

    /** @return array<string, array{string, bool}> */
    public static function invalidImages(): array
    {
        $images = [
            'malformed' => '<svg xmlns="http://www.w3.org/2000/svg"><rect></svg>',
            'html' => '<html><body>hello</body></html>',
            'nested-svg' => '<html><svg xmlns="http://www.w3.org/2000/svg"/></html>',
            'wrong-namespace' => '<svg xmlns="https://evil.test"/>',
            'entity' => '<!DOCTYPE svg [<!ENTITY payload SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg"><text>&payload;</text></svg>',
        ];
        $cases = [];
        foreach ($images as $name => $image) {
            $cases[$name . '-upload'] = [$image, false];
            $cases[$name . '-import'] = [$image, true];
        }

        return $cases;
    }

    #[DataProvider('invalidImages')]
    public function testRejectsInvalidSvgWithoutSaving(string $image, bool $import): void
    {
        config(['filesystems.image_disk' => 'public']);
        Storage::fake('public');

        try {
            $this->store($image, $import);
            self::fail('Invalid image must be rejected.');
        } catch (InvalidBase64ImageException | InvalidRemoteImageException $exception) {
            self::assertInstanceOf($import ? InvalidRemoteImageException::class : InvalidBase64ImageException::class, $exception);
            self::assertSame([], Storage::disk('public')->allFiles());
        }
    }

    #[DataProvider('operations')]
    public function testFailedSvgWriteDoesNotReturnSuccessfulPath(bool $import): void
    {
        config(['filesystems.image_disk' => 's3']);
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->once()->withArgs(static fn (string $path, string $contents): bool =>
            str_ends_with($path, '.webp') && str_starts_with($contents, 'RIFF'))
            ->andReturn(false);
        Storage::shouldReceive('disk')->with('s3')->andReturn($disk);

        $this->expectException(UnableToWriteFile::class);
        $this->store(self::SVG, $import);
    }

    private function store(string $image, bool $import): string
    {
        $imageService = $this->app()->make(ImageServiceInterface::class);
        if ($import) {
            Http::fake(['https://example.test/image.svg' => Http::response($image)]);

            return (string) $imageService->importFromUrl('https://example.test/image.svg');
        }

        return (string) $imageService->upload(base64_encode($image));
    }
}
