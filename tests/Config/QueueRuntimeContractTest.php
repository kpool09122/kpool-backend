<?php

declare(strict_types=1);

namespace Tests\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class QueueRuntimeContractTest extends TestCase
{
    #[DataProvider('environments')]
    public function testScheduleAndRoutes(string $environment, int $scheduledCount): void
    {
        $code = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make(Illuminate\Contracts\Console\Kernel::class)->all();
$events = $app->make(Illuminate\Console\Scheduling\Schedule::class)->events();
$routes = $app->make('router')->getRoutes();
echo json_encode([
    'schedule' => array_map(fn ($event) => ['expression' => $event->expression, 'timezone' => $event->timezone, 'command' => $event->command], $events),
    'cloudTasks' => array_values(array_filter(array_map(fn ($route) => $route->uri(), iterator_to_array($routes)), fn ($uri) => str_starts_with($uri, 'internal/queue'))),
]);
PHP;
        $process = new Process([PHP_BINARY, '-r', $code], dirname(__DIR__, 2), ['APP_ENV' => $environment, 'CLOUD_TASKS_HANDLER_ENABLED' => 'true']);
        $process->mustRun();
        $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($result);
        self::assertIsArray($result['schedule']);
        self::assertSame([], $result['cloudTasks']);
        self::assertCount($scheduledCount, $result['schedule']);
        if ($scheduledCount === 1) {
            self::assertIsArray($result['schedule'][0]);
            self::assertIsString($result['schedule'][0]['command']);
            self::assertSame('0 3 1 * *', $result['schedule'][0]['expression']);
            self::assertSame('Asia/Tokyo', $result['schedule'][0]['timezone']);
            self::assertStringContainsString('wiki:process-role-promotion', $result['schedule'][0]['command']);
        }
    }

    /** @return array<string, array{string, int}> */
    public static function environments(): array
    {
        return ['production' => ['production', 0], 'staging' => ['staging', 1], 'local' => ['local', 1], 'testing' => ['testing', 1]];
    }
}
