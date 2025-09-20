<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
// Add conditional-db related imports
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Automatically enables package discoveries.
     *
     * @var bool
     */
    protected $enablesPackageDiscoveries = true;

    // Enable DB usage only for tests marked with @group useDb
    protected bool $useDb = false;

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Application\Providers\Wiki\DomainServiceProvider::class,
            \Application\Providers\Wiki\UseCaseServiceProvider::class,
            \Application\Providers\SharedServiceProvider::class,
            \Application\Providers\SiteManagement\DomainServiceProvider::class,
            \Application\Providers\SiteManagement\UseCaseServiceProvider::class,
        ];
    }

    /**
     * Per-test setup with optional DB boot if the test is grouped as 'useDb'.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Decide DB usage per test method based on group annotation
        if (in_array('useDb', $this->groups(), true)) {
            $this->useDb = true;

            // Configure testing connection at runtime
            $this->app['config']->set('database.default', 'testing');
            $this->app['config']->set('database.connections.testing', [
                'driver' => 'pgsql',
                'host' => 'testing_db',
                'port' => '5432',
                'database' => 'kpool',
                'username' => 'kpool',
                'password' => 'secret',
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ]);

            // Register migrations and run them once per process
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            if (! RefreshDatabaseState::$migrated) {
                $this->artisan('migrate', ['--database' => 'testing']);
                RefreshDatabaseState::$migrated = true;
            }

            // Wrap each DB test in a transaction for isolation
            DB::beginTransaction();
        }
    }

    /**
     * Per-test teardown with optional DB cleanup.
     */
    protected function tearDown(): void
    {
        if ($this->useDb) {
            // Rollback any changes and disconnect
            DB::rollBack();
            DB::disconnect();
        }

        parent::tearDown();
    }

    /**
     * Define environment setup (no-op for non-DB tests).
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Intentionally left blank; DB is configured conditionally in setUp()
    }

    /**
     * Define database migrations (no-op for non-DB tests).
     *
     * @return void
     */
    protected function defineDatabaseMigrations(): void
    {
        // Intentionally left blank; migrations are run conditionally in setUp()
    }
}
