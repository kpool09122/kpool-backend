<?php

declare(strict_types=1);

namespace Tests;

use Application\Models\Identity\Identity;
use Application\Providers\Account\DomainServiceProvider as AccountDomainServiceProvider;
use Application\Providers\Account\EventServiceProvider as AccountEventServiceProvider;
use Application\Providers\Account\UseCaseServiceProvider as AccountUseCaseServiceProvider;
use Application\Providers\ClientServiceProvider;
use Application\Providers\Identity\DomainServiceProvider as IdentityDomainServiceProvider;
use Application\Providers\Identity\EventServiceProvider as IdentityEventServiceProvider;
use Application\Providers\Identity\UseCaseServiceProvider as IdentityUseCaseServiceProvider;
use Application\Providers\Monetization\DomainServiceProvider as MonetizationDomainServiceProvider;
use Application\Providers\Monetization\EventServiceProvider as MonetizationEventServiceProvider;
use Application\Providers\Monetization\UseCaseServiceProvider as MonetizationUseCaseServiceProvider;
use Application\Providers\SharedServiceProvider;
use Application\Providers\SiteManagement\DomainServiceProvider as SiteManagementDomainServiceProvider;
// Add conditional-db related imports
use Application\Providers\SiteManagement\UseCaseServiceProvider as SiteManagementUseCaseServiceProvider;
use Application\Providers\Wiki\DomainServiceProvider;
use Application\Providers\Wiki\EventServiceProvider;
use Application\Providers\Wiki\UseCaseServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use LogicException;
use Mockery;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Override;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Automatically enables package discoveries.
     *
     * @var bool
     */
    #[Override]
    protected $enablesPackageDiscoveries = true;

    // Enable DB usage only for tests belonging to the 'useDb' group (now defined via #[Group('useDb')]).
    protected bool $useDb = false;

    /**
     * Get package providers.
     *
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            DomainServiceProvider::class,
            UseCaseServiceProvider::class,
            EventServiceProvider::class,
            SharedServiceProvider::class,
            SiteManagementDomainServiceProvider::class,
            SiteManagementUseCaseServiceProvider::class,
            IdentityUseCaseServiceProvider::class,
            IdentityDomainServiceProvider::class,
            IdentityEventServiceProvider::class,
            AccountUseCaseServiceProvider::class,
            AccountDomainServiceProvider::class,
            AccountEventServiceProvider::class,
            MonetizationUseCaseServiceProvider::class,
            MonetizationDomainServiceProvider::class,
            MonetizationEventServiceProvider::class,
            ClientServiceProvider::class,
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

            // Run migrations once per process without Testbench's auto-rollback machinery
            if (! RefreshDatabaseState::$migrated) {
                $this->artisan('migrate:fresh', [
                    '--path' => realpath(__DIR__ . '/../database/migrations'),
                    '--realpath' => true,
                ]);
                RefreshDatabaseState::$migrated = true;
            }

            // Wrap each DB test in a transaction for isolation
            DB::beginTransaction();
        } else {
            $this->setPolicyEvaluatorResult(true);
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
     * @param  Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Encryption (Crypt) requires APP_KEY. This project doesn't ship .env.testing,
        // so ensure a deterministic key is always present for tests.
        if (! $app['config']->get('app.key')) {
            $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('0', 32)));
        }
        if (! $app['config']->get('app.cipher')) {
            $app['config']->set('app.cipher', 'AES-256-CBC');
        }

        // Auth設定: 正しいIdentityモデルを使用
        $app['config']->set('auth.providers.users.model', Identity::class);
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

    protected function app(): Application
    {
        if (! $this->app instanceof Application) {
            throw new LogicException('The application has not been initialized.');
        }

        return $this->app;
    }

    protected function setPolicyEvaluatorResult(bool $allowed): void
    {
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->andReturn($allowed);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
    }
}
