<?php

declare(strict_types=1);

use Application\Http\Action\Wiki\Agency\Command\ApproveAgency\ApproveAgencyAction;
use Application\Http\Action\Wiki\Wiki\Command\ApproveWiki\ApproveWikiAction;
use Illuminate\Support\Facades\Route;

Route::post('/agency/{agencyId}/approve', ApproveAgencyAction::class);
Route::post('/wiki/{wikiId}/approve', ApproveWikiAction::class);
