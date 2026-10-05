<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Illuminate\Support\Facades\Storage;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Tests\TestCase;

class S3FilesystemConfigurationTest extends TestCase
{
    public function testProductionDisksAreSeparatedAndUseTaskRoleCredentials(): void
    {
        $environment = [
            'IMAGE_STORAGE_DISK' => 's3',
            'VERIFICATION_DOCUMENTS_DRIVER' => 's3',
            'AWS_PUBLIC_IMAGES_BUCKET' => 'public-images',
            'AWS_PRIVATE_FILES_BUCKET' => 'private-files',
            'IMAGE_BASE_URL' => 'https://images.example.test/',
            'AWS_ACCESS_KEY_ID' => null,
            'AWS_SECRET_ACCESS_KEY' => null,
            'AWS_SESSION_TOKEN' => null,
        ];
        $original = [];
        foreach ($environment as $key => $value) {
            $original[$key] = [
                'env' => $_ENV[$key] ?? null,
                'server' => $_SERVER[$key] ?? null,
                'process' => getenv($key),
            ];
        }

        try {
            foreach ($environment as $key => $value) {
                if ($value === null) {
                    unset($_ENV[$key], $_SERVER[$key]);
                    putenv($key);
                } else {
                    $_ENV[$key] = $_SERVER[$key] = $value;
                    putenv($key . '=' . $value);
                }
            }
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
            self::assertNull($config['disks']['s3']['token']);
            self::assertNull($config['disks']['verification-documents']['key']);
            self::assertNull($config['disks']['verification-documents']['secret']);
            self::assertNull($config['disks']['verification-documents']['token']);
            config(['filesystems' => $config]);
            Storage::forgetDisk('s3');
            self::assertSame('https://images.example.test/images/example.webp', ImageUrl::fromPath('images/example.webp'));
        } finally {
            foreach ($original as $key => $values) {
                if ($values['env'] === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $values['env'];
                }
                if ($values['server'] === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $values['server'];
                }
                putenv($values['process'] === false ? $key : $key . '=' . $values['process']);
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
