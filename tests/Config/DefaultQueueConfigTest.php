<?php

declare(strict_types=1);

namespace Tests\Config;

use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DefaultQueueConfigTest extends TestCase
{
    #[DataProvider('environments')]
    public function testDefaultConnectionDependsOnEnvironment(string $environment, string $driver): void
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->addAdapter(PutenvAdapter::class)->make();
        $original = $repository->get('APP_ENV');
        $originalConnection = $repository->get('QUEUE_CONNECTION');
        $repository->clear('QUEUE_CONNECTION');
        $repository->clear('APP_ENV');
        $repository->set('APP_ENV', $environment);

        try {
            $config = require __DIR__ . '/../../config/queue.php';
            self::assertIsArray($config);
            self::assertIsArray($config['connections']);
            self::assertIsArray($config['connections']['sqs']);
            $this->assertSame($driver, $config['default']);
            $this->assertSame('sqs', $config['connections']['sqs']['driver']);
            $this->assertFalse($config['connections']['sqs']['after_commit']);
        } finally {
            $repository->clear('QUEUE_CONNECTION');
            if ($originalConnection !== null) {
                $repository->set('QUEUE_CONNECTION', $originalConnection);
            }
            $repository->clear('APP_ENV');
            if ($original !== null) {
                $repository->set('APP_ENV', $original);
            }
        }
    }

    public function testExplicitRedisOverrideResolvesAllLogicalQueues(): void
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->addAdapter(PutenvAdapter::class)->make();
        $originalEnvironment = $repository->get('APP_ENV');
        $originalConnection = $repository->get('QUEUE_CONNECTION');
        $repository->clear('APP_ENV');
        $repository->clear('QUEUE_CONNECTION');
        $repository->set('APP_ENV', 'production');
        $repository->set('QUEUE_CONNECTION', 'redis');

        try {
            $config = require __DIR__ . '/../../config/queue.php';
            self::assertIsArray($config);
            self::assertIsArray($config['connections']);
            self::assertIsArray($config['connections']['redis']);
            self::assertIsArray($config['connections']['sqs']);
            self::assertSame('redis', $config['default']);
            self::assertSame('redis', $config['connections']['redis']['driver']);
            self::assertSame('default', $config['connections']['redis']['queue']);
            self::assertSame(300, $config['connections']['redis']['retry_after']);
            self::assertFalse($config['connections']['redis']['after_commit']);
            self::assertSame(['webhook' => 'webhook', 'settlement' => 'settlement'], $config['routing']);
            self::assertArrayNotHasKey('key', $config['connections']['sqs']);
            self::assertArrayNotHasKey('secret', $config['connections']['sqs']);
        } finally {
            $repository->clear('APP_ENV');
            $repository->clear('QUEUE_CONNECTION');
            if ($originalEnvironment !== null) {
                $repository->set('APP_ENV', $originalEnvironment);
            }
            if ($originalConnection !== null) {
                $repository->set('QUEUE_CONNECTION', $originalConnection);
            }
        }
    }

    /** @return array<string, array{string, string}> */
    public static function environments(): array
    {
        return [
            'local' => ['local', 'redis'],
            'testing' => ['testing', 'redis'],
            'staging' => ['staging', 'sqs'],
            'production' => ['production', 'sqs'],
            'preview' => ['preview', 'sqs'],
        ];
    }
}
