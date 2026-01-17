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
        \Application\Providers\SharedServiceProvider::class,
        \Application\Providers\Monetization\DomainServiceProvider::class,
        \Application\Providers\Monetization\UseCaseServiceProvider::class,
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
