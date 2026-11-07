<?php

declare(strict_types=1);
use Application\Http\Action\Analyze\App\Query\GetAppDailySummaryReport\GetAppDailySummaryReportAction;
use Application\Http\Action\Analyze\Principal\Command\CreatePrincipal\CreatePrincipalAction;
use Application\Http\Action\Analyze\Principal\Query\GetCurrentPrincipal\GetCurrentPrincipalAction;
use Application\Http\Action\Analyze\Wiki\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportAction;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.api', 'account.active', 'principal.active', 'resolve.actor', 'resolve.account', 'rate-limit:screen,command'])->group(function () {
    Route::post('/principals', CreatePrincipalAction::class);
});

Route::middleware(['auth.api', 'resolve.actor', 'resolve.account', 'rate-limit:screen,query'])->group(function () {
    Route::get('/principal/me', GetCurrentPrincipalAction::class);
});

Route::middleware(['auth.api', 'account.active', 'principal.active', 'resolve.actor', 'resolve.account', 'resolve.analyze', 'rate-limit:screen,query'])->group(function () {
    Route::get('/app/daily-summary', GetAppDailySummaryReportAction::class);
    Route::get('/wiki/daily-summary', GetWikiDailySummaryReportAction::class);
});
