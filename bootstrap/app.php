<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/wiki_private_api.php',
        apiPrefix: 'api/wiki',
    )
    ->withCommands([
        __DIR__ . '/../application/Console/Commands',
    ])
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
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(app(\Application\Http\Exceptions\Handler::class));
    })
    ->create();

$app->useAppPath(__DIR__ . '/../application');
$app->useLangPath(__DIR__ . '/../resources/lang');

return $app;
