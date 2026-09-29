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
            self::assertIsArray($config['connections']['cloudtasks']);
            $this->assertSame($driver, $config['default']);
            $this->assertSame('cloudtasks', $config['connections']['cloudtasks']['driver']);
            $this->assertFalse($config['connections']['cloudtasks']['after_commit']);
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

    /** @return array<string, array{string, string}> */
    public static function environments(): array
    {
        return [
            'local' => ['local', 'redis'],
            'testing' => ['testing', 'redis'],
            'staging' => ['staging', 'cloudtasks'],
            'production' => ['production', 'cloudtasks'],
            'preview' => ['preview', 'cloudtasks'],
        ];
    }
}
