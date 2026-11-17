<?php

declare(strict_types=1);

use Application\Http\Action\Mcp\ExchangeTokenAction;
use Application\Http\Action\Mcp\GetAuthorizationServerMetadataAction;
use Application\Http\Action\Mcp\GetProtectedResourceMetadataAction;
use Application\Http\Action\Mcp\HandleMcpAction;
use Application\Http\Middleware\EnsureMcpEnabled;
use Illuminate\Support\Facades\Route;

// Deliberately outside the Cookie/session/CSRF and internal-Bearer API stack.
Route::middleware([EnsureMcpEnabled::class, 'throttle:120,1'])->group(function () {
    Route::match(['GET', 'POST', 'DELETE'], '/mcp', HandleMcpAction::class);
    Route::get('/.well-known/oauth-protected-resource/mcp', GetProtectedResourceMetadataAction::class);
    Route::get('/.well-known/oauth-protected-resource', GetProtectedResourceMetadataAction::class);
    Route::get('/.well-known/oauth-authorization-server', GetAuthorizationServerMetadataAction::class);
    Route::post('/oauth/token', ExchangeTokenAction::class);
});
