<?php

declare(strict_types=1);

use Application\Http\Action\Wiki\Wiki\Command\ApproveWiki\ApproveWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\AutoCreateWiki\AutoCreateWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\CreateWiki\CreateWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\EditWiki\EditWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\MergeWiki\MergeWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\PublishWiki\PublishWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\RejectWiki\RejectWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\RollbackWiki\RollbackWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\SubmitWiki\SubmitWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\TranslateWiki\TranslateWikiAction;
use Illuminate\Support\Facades\Route;

Route::post('/wiki/create', CreateWikiAction::class);
Route::post('/wiki/auto-create', AutoCreateWikiAction::class);
Route::post('/wiki/{wikiId}/approve', ApproveWikiAction::class);
Route::post('/wiki/{wikiId}/edit', EditWikiAction::class);
Route::post('/wiki/{wikiId}/merge', MergeWikiAction::class);
Route::post('/wiki/{wikiId}/publish', PublishWikiAction::class);
Route::post('/wiki/{wikiId}/reject', RejectWikiAction::class);
Route::post('/wiki/{wikiId}/rollback', RollbackWikiAction::class);
Route::post('/wiki/{wikiId}/submit', SubmitWikiAction::class);
Route::post('/wiki/{wikiId}/translate', TranslateWikiAction::class);
