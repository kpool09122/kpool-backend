<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Source\Shared\Infrastructure\Service\ImageService;
use Tests\TestCase;

class ImageStorageFailureTest extends TestCase
{
    /** @return array<string, array{bool}> */
    public static function operations(): array
    {
        return ['upload' => [false], 'import' => [true]];
    }

    #[DataProvider('operations')]
    public function testFailedWriteDoesNotReturnSuccessfulPath(bool $import): void
    {
        config(['filesystems.image_disk' => 's3']);
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('s3')->andReturn($disk);
        $data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', true);
        self::assertIsString($data);
        $service = new ImageService(new NullLogger());
        Http::fake(['https://example.test/image.png' => Http::response($data)]);

        $this->expectException(UnableToWriteFile::class);
        if ($import) {
            $service->importFromUrl('https://example.test/image.png');
        } else {
            $service->upload(base64_encode($data));
        }
    }
}
