<?php

declare(strict_types=1);

use Application\Http\Exceptions\Handler;
use Application\Http\Middleware\EnforceApiRateLimit;
use Application\Http\Middleware\EnsureAccountActive;
use Application\Http\Middleware\EnsureAuthenticated;
use Application\Http\Middleware\PreventRequestForgery;
use Application\Http\Middleware\ResolveAccountContext;
use Application\Http\Middleware\ResolveActorContext;
use Application\Http\Middleware\ResolveWikiContext;
use Application\Http\Middleware\ResolveSiteManagementContext;
use Application\Http\Middleware\StartApplicationSession;
use Application\Providers\Account\DomainServiceProvider as AccountDomainServiceProvider;
use Application\Providers\Account\EventServiceProvider as AccountEventServiceProvider;
use Application\Providers\Account\UseCaseServiceProvider as AccountUseCaseServiceProvider;
use Application\Providers\ClientServiceProvider;
use Application\Providers\Identity\DomainServiceProvider as IdentityDomainServiceProvider;
use Application\Providers\Identity\EventServiceProvider as IdentityEventServiceProvider;
use Application\Providers\Identity\UseCaseServiceProvider as IdentityUseCaseServiceProvider;
use Application\Providers\Monetization\DomainServiceProvider as MonetizationDomainServiceProvider;
use Application\Providers\Monetization\UseCaseServiceProvider as MonetizationUseCaseServiceProvider;
use Application\Providers\SharedServiceProvider;
use Application\Providers\SiteManagement\DomainServiceProvider as SiteManagementDomainServiceProvider;
use Application\Providers\SiteManagement\UseCaseServiceProvider as SiteManagementUseCaseServiceProvider;
use Application\Providers\Wiki\DomainServiceProvider as WikiDomainServiceProvider;
use Application\Providers\Wiki\EventServiceProvider as WikiEventServiceProvider;
use Application\Providers\Wiki\UseCaseServiceProvider as WikiUseCaseServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration as SentryIntegration;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        health: '/health',
        then: function () {
            Route::prefix('api/v1')->middleware(['api', 'session'])->group(function () {
                Route::prefix('identity')->group(base_path('routes/v1/identity_api.php'));
                Route::prefix('monetization')->middleware(['auth.api', 'resolve.actor'])
                    ->group(base_path('routes/v1/monetization_api.php'));
                Route::prefix('account')->group(base_path('routes/v1/account_api.php'));
                Route::prefix('site-management')->group(base_path('routes/v1/site_management_api.php'));
                Route::prefix('wiki')->group(base_path('routes/v1/wiki_api.php'));
            });
            Route::prefix('webhook')
                ->group(base_path('routes/webhook.php'));
        },
    )
    ->withCommands([
        __DIR__ . '/../application/Console/Commands',
    ])
    ->withSchedule(function (Schedule $schedule) {
        // Wiki Collaborator promotion/demotion: 1st of each month at 03:00 JST
        // Production is owned by EventBridge Scheduler; never start the same batch twice.
        if (! app()->environment('production')) {
            $schedule->command('wiki:process-role-promotion')
                ->monthlyOn(1, '03:00')->timezone('Asia/Tokyo');
        }
    })
    ->withProviders([
        // Shared
        SharedServiceProvider::class,
        ClientServiceProvider::class,

        // Account
        AccountDomainServiceProvider::class,
        AccountUseCaseServiceProvider::class,
        AccountEventServiceProvider::class,

        // Identity
        IdentityDomainServiceProvider::class,
        IdentityUseCaseServiceProvider::class,
        IdentityEventServiceProvider::class,

        // Monetization
        MonetizationDomainServiceProvider::class,
        MonetizationUseCaseServiceProvider::class,

        // SiteManagement
        SiteManagementDomainServiceProvider::class,
        SiteManagementUseCaseServiceProvider::class,

        // Wiki
        WikiDomainServiceProvider::class,
        WikiUseCaseServiceProvider::class,
        WikiEventServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trimStrings(except: ['confirmationIdentityName']);
        $middleware->group('session', [
            EncryptCookies::class,
            StartApplicationSession::class,
            PreventRequestForgery::class,
        ]);
        $middleware->group('auth.api', [
            EnsureAuthenticated::class,
            EnsureAccountActive::class,
        ]);
        $middleware->alias([
            'resolve.actor' => ResolveActorContext::class,
            'resolve.account' => ResolveAccountContext::class,
            'resolve.wiki' => ResolveWikiContext::class,
            'resolve.site-management' => ResolveSiteManagementContext::class,
            'rate-limit' => EnforceApiRateLimit::class,
        ]);
        $middleware->preventRequestForgery(except: [
            'webhook/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        if (app()->environment('production') && filled(config('sentry.dsn'))) {
            SentryIntegration::handles($exceptions);
        }

        $exceptions->render(app(Handler::class));
    })
    ->create();

$app->useAppPath(__DIR__ . '/../application');
$app->useLangPath(__DIR__ . '/../resources/lang');

return $app;
