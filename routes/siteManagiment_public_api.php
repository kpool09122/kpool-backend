<?php

declare(strict_types=1);

use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactAction;
use Application\Http\Action\SiteManagement\Contact\Query\ListMyContacts\ListMyContactsAction;
use Illuminate\Support\Facades\Route;

Route::post('/contact/submit/v{version}', SubmitContactAction::class)->whereNumber('version');
Route::get('/my/contact', ListMyContactsAction::class)->middleware(['auth.api', 'resolve.actor']);
