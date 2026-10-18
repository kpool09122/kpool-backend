<?php

declare(strict_types=1);

use Application\Http\Action\SiteManagement\Announcement\Command\CreateAnnouncement\CreateAnnouncementAction;
use Application\Http\Action\SiteManagement\Announcement\Command\DeleteAnnouncement\DeleteAnnouncementAction;
use Application\Http\Action\SiteManagement\Announcement\Command\PublishAnnouncement\PublishAnnouncementAction;
use Application\Http\Action\SiteManagement\Announcement\Command\TranslateAnnouncement\TranslateAnnouncementAction;
use Application\Http\Action\SiteManagement\Announcement\Command\UpdateAnnouncement\UpdateAnnouncementAction;
use Application\Http\Action\SiteManagement\Announcement\Query\GetAnnouncement\GetAnnouncementAction;
use Application\Http\Action\SiteManagement\Announcement\Query\GetDraftAnnouncement\GetDraftAnnouncementAction;
use Application\Http\Action\SiteManagement\Announcement\Query\ListAnnouncements\ListAnnouncementsAction;
use Application\Http\Action\SiteManagement\Announcement\Query\ListDraftAnnouncements\ListDraftAnnouncementsAction;
use Application\Http\Action\SiteManagement\Contact\Command\ReplyContact\ReplyContactAction;
use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactAction;
use Application\Http\Action\SiteManagement\Contact\Query\GetContactDetail\GetContactDetailAction;
use Application\Http\Action\SiteManagement\Contact\Query\GetMyContactDetail\GetMyContactDetailAction;
use Application\Http\Action\SiteManagement\Contact\Query\ListContacts\ListContactsAction;
use Application\Http\Action\SiteManagement\Contact\Query\ListContactsByPrincipal\ListContactsByPrincipalAction;
use Application\Http\Action\SiteManagement\Contact\Query\ListMyContacts\ListMyContactsAction;
use Application\Http\Action\SiteManagement\Principal\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersAction;
use Application\Http\Action\SiteManagement\Principal\Query\ListPrincipalGroups\ListPrincipalGroupsAction;
use Illuminate\Support\Facades\Route;

Route::middleware('rate-limit:screen,command')->group(function () {
    Route::post('/contact/submit', SubmitContactAction::class);
});

Route::middleware('rate-limit:screen,query')->group(function () {
    Route::get('/announcements', ListAnnouncementsAction::class);
    Route::get('/announcements/{announcementIdentifier}', GetAnnouncementAction::class);
});

Route::middleware(['auth.api', 'resolve.actor', 'resolve.account', 'resolve.site-management'])->group(function () {
    Route::middleware('rate-limit:screen,command')->group(function () {
        Route::post('/draft-announcements', CreateAnnouncementAction::class);
        Route::put('/draft-announcements/{announcementIdentifier}', UpdateAnnouncementAction::class);
        Route::post('/draft-announcements/{announcementIdentifier}/translate', TranslateAnnouncementAction::class);
        Route::post('/draft-announcements/{announcementIdentifier}/publish', PublishAnnouncementAction::class);
        Route::delete('/draft-announcements/{announcementIdentifier}', DeleteAnnouncementAction::class);
        Route::patch('/principal-groups/members', UpdatePrincipalGroupMembersAction::class);
        Route::post('/contacts/{contactIdentifier}/replies', ReplyContactAction::class);
    });

    Route::middleware('rate-limit:screen,query')->group(function () {
        Route::get('/draft-announcements', ListDraftAnnouncementsAction::class);
        Route::get('/draft-announcements/{announcementIdentifier}', GetDraftAnnouncementAction::class);
        Route::get('/principal-groups', ListPrincipalGroupsAction::class);
        Route::get('/my/contact', ListMyContactsAction::class);
        Route::get('/my/contact/{contactIdentifier}', GetMyContactDetailAction::class);
        Route::get('/contact/principals/{principalIdentifier}', ListContactsByPrincipalAction::class);
        Route::get('/contact/principals/{principalIdentifier}/{contactIdentifier}', GetContactDetailAction::class);
        Route::get('/contacts', ListContactsAction::class);
    });
});
