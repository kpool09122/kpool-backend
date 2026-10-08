<?php

declare(strict_types=1);

use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactAction;
use Application\Http\Action\SiteManagement\Contact\Query\GetContactDetail\GetContactDetailAction;
use Application\Http\Action\SiteManagement\Contact\Query\GetMyContactDetail\GetMyContactDetailAction;
use Application\Http\Action\SiteManagement\Contact\Query\ListContacts\ListContactsAction;
use Application\Http\Action\SiteManagement\Contact\Query\ListContactsByIdentity\ListContactsByIdentityAction;
use Application\Http\Action\SiteManagement\Contact\Query\ListMyContacts\ListMyContactsAction;
use Illuminate\Support\Facades\Route;

Route::middleware('rate-limit:screen,command')->group(function () {
    Route::post('/contact/submit/v{version}', SubmitContactAction::class)->whereNumber('version');
});

Route::middleware(['auth.api', 'resolve.actor', 'resolve.account', 'resolve.site-management'])->group(function () {
    Route::middleware('rate-limit:screen,query')->group(function () {
        Route::get('/my/contact', ListMyContactsAction::class);
        Route::get('/my/contact/{contactIdentifier}', GetMyContactDetailAction::class);
        Route::get('/contact/identities/{identityIdentifier}', ListContactsByIdentityAction::class);
        Route::get('/contact/identities/{identityIdentifier}/{contactIdentifier}', GetContactDetailAction::class);
        Route::get('/contacts', ListContactsAction::class);
    });
});
