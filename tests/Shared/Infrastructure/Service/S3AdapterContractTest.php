<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Aws\CommandInterface;
use Aws\History;
use Aws\Middleware;
use Aws\MockHandler;
use Aws\Result;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class S3AdapterContractTest extends TestCase
{
    public function testRealAdapterUsesSeparateBucketsAndPrefixesWithoutPublicAcl(): void
    {
        /** @var array{disks: array<string, array<string, mixed>>} $config */
        $config = require __DIR__ . '/../../../../config/filesystems.php';
        foreach ([
            ['s3', 'public-images', '', 'images/example.webp', 'images/example.webp'],
            ['verification-documents', 'private-files', 'verification-documents', 'accounts/example/document.pdf', 'verification-documents/accounts/example/document.pdf'],
        ] as [$name, $bucket, $root, $path, $key]) {
            $handler = new MockHandler();
            $handler->append(new Result(), new Result(['Body' => Utils::streamFor('contents')]), new Result(), new Result());
            $diskConfig = array_merge($config['disks']['s3'], [
                'bucket' => $bucket,
                'root' => $root,
                'key' => 'test-key',
                'secret' => 'test-secret',
                'handler' => $handler,
            ]);
            if ($name === 'verification-documents') {
                unset($diskConfig['url']);
            }
            config(['filesystems.disks.' . $name => $diskConfig]);
            Storage::forgetDisk($name);
            $disk = Storage::disk($name);
            self::assertInstanceOf(AwsS3V3Adapter::class, $disk);
            $history = new History();
            $disk->getClient()->getHandlerList()->appendSign(Middleware::history($history));
            self::assertTrue($disk->put($path, 'contents'));
            self::assertSame('contents', $disk->get($path));
            self::assertTrue($disk->exists($path));
            self::assertTrue($disk->delete($path));
            /** @var list<array{command: CommandInterface}> $history */
            $history = iterator_to_array($history);
            self::assertCount(4, $history);
            $operations = [];
            foreach ($history as $entry) {
                $operations[] = $entry['command']->getName();
                self::assertSame($bucket, $entry['command']['Bucket']);
                self::assertSame($key, $entry['command']['Key']);
            }
            self::assertSame(['PutObject', 'GetObject', 'HeadObject', 'DeleteObject'], $operations);
            self::assertSame('bucket-owner-full-control', $history[0]['command']['ACL']);
        }
    }
}
