<?php

declare(strict_types=1);

namespace Tests\Database\Seeders;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\TestAccountSeeder;
use Database\Seeders\WikiEditorSampleSeeder;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SeederEnvironmentTest extends TestCase
{
    #[DataProvider('environments')]
    public function testDatabaseSeederSelectsSampleDataOnlyInDevelopment(string $environment, bool $samplesAllowed): void
    {
        $this->app()->instance('env', $environment);

        foreach ([MasterDataSeeder::class, TestAccountSeeder::class, WikiEditorSampleSeeder::class] as $class) {
            $seeder = Mockery::mock($class)->makePartial();
            $seeder->shouldReceive('run')->times($class === MasterDataSeeder::class || $samplesAllowed ? 1 : 0);
            $this->app()->instance($class, $seeder);
        }

        $seeder = $this->app()->make(DatabaseSeeder::class);
        $seeder->setContainer($this->app());
        $seeder->run();
    }

    #[DataProvider('protectedEnvironments')]
    public function testDirectSampleSeederExecutionDoesNotAccessDatabase(string $environment): void
    {
        $this->app()->instance('env', $environment);
        DB::shouldReceive('table')->never();

        $this->app()->make(TestAccountSeeder::class)->run();
        $this->app()->make(WikiEditorSampleSeeder::class)->run();
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function environments(): array
    {
        return [
            'local' => ['local', true],
            'testing' => ['testing', true],
            'production' => ['production', false],
            'staging' => ['staging', false],
            'unknown' => ['unknown', false],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function protectedEnvironments(): array
    {
        return [
            'production' => ['production'],
            'staging' => ['staging'],
            'unknown' => ['unknown'],
        ];
    }
}
