<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Illuminate\Support\Env;
use Illuminate\Support\Facades\Storage;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Tests\TestCase;

class S3FilesystemConfigurationTest extends TestCase
{
    public function testProductionDisksAreSeparatedAndUseTaskRoleCredentials(): void
    {
        Env::getRepository()->set('IMAGE_STORAGE_DISK', 's3');
        Env::getRepository()->set('VERIFICATION_DOCUMENTS_DRIVER', 's3');
        Env::getRepository()->set('AWS_PUBLIC_IMAGES_BUCKET', 'public-images');
        Env::getRepository()->set('AWS_PRIVATE_FILES_BUCKET', 'private-files');
        Env::getRepository()->set('IMAGE_BASE_URL', 'https://images.example.test/');

        try {
            /** @var array{image_disk: string, disks: array<string, array<string, mixed>>} $config */
            $config = require __DIR__ . '/../../../../config/filesystems.php';
            self::assertSame('s3', $config['image_disk']);
            self::assertSame('public-images', $config['disks']['s3']['bucket']);
            self::assertSame('private-files', $config['disks']['verification-documents']['bucket']);
            self::assertSame('verification-documents', $config['disks']['verification-documents']['root']);
            self::assertSame('private', $config['disks']['s3']['visibility']);
            self::assertSame('private', $config['disks']['verification-documents']['visibility']);
            self::assertArrayNotHasKey('url', $config['disks']['verification-documents']);
            self::assertTrue($config['disks']['s3']['throw']);
            self::assertNull($config['disks']['s3']['key']);
            self::assertNull($config['disks']['s3']['secret']);
            config(['filesystems' => $config]);
            Storage::forgetDisk('s3');
            self::assertSame('https://images.example.test/images/example.webp', ImageUrl::fromPath('images/example.webp'));
        } finally {
            foreach (['IMAGE_STORAGE_DISK', 'VERIFICATION_DOCUMENTS_DRIVER', 'AWS_PUBLIC_IMAGES_BUCKET', 'AWS_PRIVATE_FILES_BUCKET', 'IMAGE_BASE_URL'] as $key) {
                Env::getRepository()->clear($key);
            }
        }
    }

    public function testLocalDefaultsRemainLocal(): void
    {
        /** @var array{image_disk: string, disks: array<string, array<string, mixed>>} $config */
        $config = require __DIR__ . '/../../../../config/filesystems.php';
        self::assertSame('public', $config['image_disk']);
        self::assertSame('local', $config['disks']['verification-documents']['driver']);
        self::assertSame(storage_path('app/verification-documents'), $config['disks']['verification-documents']['root']);
    }
}
