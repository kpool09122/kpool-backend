<?php

declare(strict_types=1);

use Application\Http\Middleware\EnsureCloudTaskAuthenticated;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Application\Http\Middleware\StartApplicationSession;
use Application\Jobs\Wiki\ProcessRolePromotionJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Application\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration as SentryIntegration;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Stackkit\LaravelGoogleCloudTasksQueue\TaskHandler;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        then: function () {
            if (config('cloud-tasks.handler_enabled') && ! app()->environment('local', 'testing')) {
                Route::post(config('cloud-tasks.uri'), [TaskHandler::class, 'handle'])
                    ->middleware(EnsureCloudTaskAuthenticated::class)
                    ->name('cloud-tasks.handle-task');
            }

            Route::middleware(['api', 'session'])
                ->prefix('api/identity')
                ->group(base_path('routes/identity_api.php'));
            Route::middleware(['api', 'session', 'auth.api', 'resolve.actor'])
                ->prefix('api/monetization')
                ->group(base_path('routes/monetization_api.php'));
            Route::middleware(['api', 'session'])
                ->prefix('api/account')
                ->group(base_path('routes/account_api.php'));
            Route::middleware(['api', 'session'])
                ->prefix('api/site-management')
                ->group(base_path('routes/siteManagiment_public_api.php'));
//            Route::middleware(['api', 'auth.api', 'resolve.actor', 'resolve.wiki'])
            Route::middleware(['api', 'session'])
                ->prefix('api/wiki')
                ->group(base_path('routes/wiki_private_api.php'));
            Route::prefix('webhook')
                ->group(base_path('routes/webhook.php'));
        },
    )
    ->withCommands([
        __DIR__ . '/../application/Console/Commands',
    ])
    ->withSchedule(function (Schedule $schedule) {
        // Wiki Collaborator promotion/demotion: 1st of each month at 03:00 JST
        $schedule->call(function () {
            ProcessRolePromotionJob::dispatch(YearMonth::current());
        })->monthlyOn(1, '03:00')->timezone('Asia/Tokyo');
    })
    ->withProviders([
        // Shared
        \Application\Providers\SharedServiceProvider::class,
        \Application\Providers\ClientServiceProvider::class,

        // Account
        \Application\Providers\Account\DomainServiceProvider::class,
        \Application\Providers\Account\UseCaseServiceProvider::class,
        \Application\Providers\Account\EventServiceProvider::class,

        // Identity
        \Application\Providers\Identity\DomainServiceProvider::class,
        \Application\Providers\Identity\UseCaseServiceProvider::class,
        \Application\Providers\Identity\EventServiceProvider::class,

        // Monetization
        \Application\Providers\Monetization\DomainServiceProvider::class,
        \Application\Providers\Monetization\UseCaseServiceProvider::class,

        // SiteManagement
        \Application\Providers\SiteManagement\DomainServiceProvider::class,
        \Application\Providers\SiteManagement\UseCaseServiceProvider::class,

        // Wiki
        \Application\Providers\Wiki\DomainServiceProvider::class,
        \Application\Providers\Wiki\UseCaseServiceProvider::class,
        \Application\Providers\Wiki\EventServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('session', [
            EncryptCookies::class,
            StartApplicationSession::class,
            PreventRequestForgery::class,
        ]);
        $middleware->group('auth.api', [
            \Application\Http\Middleware\EnsureAuthenticated::class,
            \Application\Http\Middleware\EnsureAccountActive::class,
        ]);
        $middleware->alias([
            'resolve.actor' => \Application\Http\Middleware\ResolveActorContext::class,
            'resolve.account' => \Application\Http\Middleware\ResolveAccountContext::class,
            'resolve.wiki' => \Application\Http\Middleware\ResolveWikiContext::class,
        ]);
        $middleware->preventRequestForgery(except: [
            'webhook/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        if (env('APP_ENV') === 'production' && filled(env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')))) {
            SentryIntegration::handles($exceptions);
        }

        $exceptions->render(app(\Application\Http\Exceptions\Handler::class));
    })
    ->create();

$app->useAppPath(__DIR__ . '/../application');
$app->useLangPath(__DIR__ . '/../resources/lang');

return $app;
