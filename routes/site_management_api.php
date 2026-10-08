<?php

declare(strict_types=1);

use Application\Http\Action\SiteManagement\Contact\Command\ReplyContact\ReplyContactAction;
use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactAction;
use Application\Http\Action\SiteManagement\Contact\Query\GetContactDetail\GetContactDetailAction;
use Application\Http\Action\SiteManagement\Contact\Query\GetMyContactDetail\GetMyContactDetailAction;
use Application\Http\Action\SiteManagement\Contact\Query\ListContacts\ListContactsAction;
use Application\Http\Action\SiteManagement\Contact\Query\ListContactsByPrincipal\ListContactsByPrincipalAction;
use Application\Http\Action\SiteManagement\Contact\Query\ListMyContacts\ListMyContactsAction;
use Illuminate\Support\Facades\Route;

Route::middleware('rate-limit:screen,command')->group(function () {
    Route::post('/contact/submit/v{version}', SubmitContactAction::class)->whereNumber('version');
});

Route::middleware(['auth.api', 'resolve.actor', 'resolve.account', 'resolve.site-management'])->group(function () {
    Route::middleware('rate-limit:screen,command')->group(function () {
        Route::post('/contacts/{contactIdentifier}/replies', ReplyContactAction::class);
    });

    Route::middleware('rate-limit:screen,query')->group(function () {
        Route::get('/my/contact', ListMyContactsAction::class);
        Route::get('/my/contact/{contactIdentifier}', GetMyContactDetailAction::class);
        Route::get('/contact/principals/{principalIdentifier}', ListContactsByPrincipalAction::class);
        Route::get('/contact/principals/{principalIdentifier}/{contactIdentifier}', GetContactDetailAction::class);
        Route::get('/contacts', ListContactsAction::class);
    });
});
