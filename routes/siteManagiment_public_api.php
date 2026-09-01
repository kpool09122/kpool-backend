<?php

declare(strict_types=1);

use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactAction;
use Application\Http\Action\SiteManagement\Contact\Query\ListContactsByIdentity\ListContactsByIdentityAction;
use Application\Http\Action\SiteManagement\Contact\Query\ListMyContacts\ListMyContactsAction;
use Application\Http\Action\SiteManagement\Contact\Query\GetContactDetail\GetContactDetailAction;
use Application\Http\Action\SiteManagement\Contact\Query\GetMyContactDetail\GetMyContactDetailAction;
use Illuminate\Support\Facades\Route;

Route::post('/contact/submit/v{version}', SubmitContactAction::class)->whereNumber('version');
Route::get('/my/contact', ListMyContactsAction::class)->middleware(['auth.api', 'resolve.actor']);
Route::get('/my/contact/{contactIdentifier}', GetMyContactDetailAction::class)->middleware(['auth.api', 'resolve.actor']);
Route::get('/contact/identities/{identityIdentifier}', ListContactsByIdentityAction::class)
    ->middleware(['auth.api', 'resolve.actor']);
Route::get('/contact/identities/{identityIdentifier}/{contactIdentifier}', GetContactDetailAction::class)
    ->middleware(['auth.api', 'resolve.actor']);
