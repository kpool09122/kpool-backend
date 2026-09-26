<?php

declare(strict_types=1);

use Application\Http\Action\Wiki\Image\Command\ApproveImage\ApproveImageAction;
use Application\Http\Action\Wiki\Image\Command\ApproveImageDeletion\ApproveImageDeletionAction;
use Application\Http\Action\Wiki\Image\Command\RejectImage\RejectImageAction;
use Application\Http\Action\Wiki\Image\Command\RejectImageDeletion\RejectImageDeletionAction;
use Application\Http\Action\Wiki\Image\Command\RequestImageDeletion\RequestImageDeletionAction;
use Application\Http\Action\Wiki\Image\Command\UploadImage\UploadImageAction;
use Application\Http\Action\Wiki\Image\Query\ListImageDeletionRequests\ListImageDeletionRequestsAction;
use Application\Http\Action\Wiki\Image\Query\ListDraftImages\ListDraftImagesAction;
use Application\Http\Action\Wiki\Image\Query\ListUploadedImages\ListUploadedImagesAction;
use Application\Http\Action\Wiki\OfficialCertification\Command\ApproveCertification\ApproveCertificationAction;
use Application\Http\Action\Wiki\OfficialCertification\Command\RejectCertification\RejectCertificationAction;
use Application\Http\Action\Wiki\OfficialCertification\Command\RequestCertification\RequestCertificationAction;
use Application\Http\Action\Wiki\OfficialCertification\Command\SyncOwnedWikiCertifications\SyncOwnedWikiCertificationsAction;
use Application\Http\Action\Wiki\OfficialCertification\Query\ListMyOfficialCertifications\ListMyOfficialCertificationsAction;
use Application\Http\Action\Wiki\OfficialCertification\Query\ListOfficialCertifications\ListOfficialCertificationsAction;
use Application\Http\Action\Wiki\Principal\Command\CreatePrincipal\CreatePrincipalAction;
use Application\Http\Action\Wiki\Principal\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersAction;
use Application\Http\Action\Wiki\Principal\Query\GetCurrentPrincipal\GetCurrentPrincipalAction;
use Application\Http\Action\Wiki\Principal\Query\ListPrincipalGroups\ListPrincipalGroupsAction;
use Application\Http\Action\Wiki\Wiki\Command\ApproveWiki\ApproveWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\AutoCreateWiki\AutoCreateWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\CreateWiki\CreateWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\DeleteWiki\DeleteWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\EditWiki\EditWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\PublishWiki\PublishWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\RejectWiki\RejectWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\SubmitWiki\SubmitWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\TranslateWiki\TranslateWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\WithdrawWiki\WithdrawWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\GetAgencyDraftWiki\GetAgencyDraftWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\GetAgencyWiki\GetAgencyWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\GetGroupDraftWiki\GetGroupDraftWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\GetGroupWiki\GetGroupWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\GetMyAgencyDraftWiki\GetMyAgencyDraftWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\GetMyGroupDraftWiki\GetMyGroupDraftWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\GetMySongDraftWiki\GetMySongDraftWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\GetMyTalentDraftWiki\GetMyTalentDraftWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\GetSongDraftWiki\GetSongDraftWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\GetSongWiki\GetSongWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\GetTalentDraftWiki\GetTalentDraftWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\GetTalentWiki\GetTalentWikiAction;
use Application\Http\Action\Wiki\Wiki\Query\ListDraftWikis\ListDraftWikisAction;
use Application\Http\Action\Wiki\Wiki\Query\ListMyDraftWikis\ListMyDraftWikisAction;
use Application\Http\Action\Wiki\Wiki\Query\ListMyOwnedWikis\ListMyOwnedWikisAction;
use Application\Http\Action\Wiki\Wiki\Query\ListRelatedProfiles\ListRelatedProfilesAction;
use Application\Http\Action\Wiki\Wiki\Query\ListRelatedWikis\ListRelatedWikisAction;
use Application\Http\Action\Wiki\Wiki\Query\ListVersionInconsistentWikis\ListVersionInconsistentWikisAction;
use Application\Http\Action\Wiki\Wiki\Query\ListWikis\ListWikisAction;
use Application\Http\Action\Wiki\Wiki\Query\SearchMasterWikis\SearchMasterWikisAction;
use Application\Http\Action\Wiki\Wiki\Query\SearchTranslationSetMasterWikis\SearchTranslationSetMasterWikisAction;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.api', 'resolve.actor', 'resolve.wiki'])->group(function () {
    Route::post('/wiki/create', CreateWikiAction::class);
    Route::post('/wiki/auto-create', AutoCreateWikiAction::class);
    Route::delete('/wiki/{wikiId}', DeleteWikiAction::class);
    Route::post('/wiki/{wikiId}/approve', ApproveWikiAction::class);
    Route::post('/wiki/{wikiId}/edit', EditWikiAction::class);
    // Disabled by #605: Route::post('/wiki/{wikiId}/merge', MergeWikiAction::class);
    Route::post('/wiki/{wikiId}/publish', PublishWikiAction::class);
    Route::post('/wiki/{wikiId}/reject', RejectWikiAction::class);
    // Disabled by #605: Route::post('/wiki/{wikiId}/rollback', RollbackWikiAction::class);
    Route::post('/wiki/{wikiId}/submit', SubmitWikiAction::class);
    Route::post('/wiki/{wikiId}/translate', TranslateWikiAction::class);
    Route::post('/wiki/{wikiId}/withdraw', WithdrawWikiAction::class);
});
Route::get('/wikis/version-inconsistencies', ListVersionInconsistentWikisAction::class)->middleware(['auth.api', 'resolve.actor']);
Route::get('/wikis/{language}/masters', SearchMasterWikisAction::class)->middleware('auth.api');
Route::get('/wiki-translation-sets/masters', SearchTranslationSetMasterWikisAction::class)->middleware('auth.api');
Route::get('/wikis/{language}', ListWikisAction::class);
Route::get('/my/draft-wikis', ListMyDraftWikisAction::class)->middleware(['auth.api', 'resolve.actor', 'resolve.wiki']);
Route::get('/my/owned-wikis', ListMyOwnedWikisAction::class)->middleware(['auth.api', 'resolve.actor', 'resolve.account']);
Route::get('/draft-wikis', ListDraftWikisAction::class)->middleware(['auth.api', 'resolve.actor', 'resolve.wiki']);
Route::get('/wiki/{language}/{slug}/related-profiles', ListRelatedProfilesAction::class);
Route::get('/wiki/{resourceType}/{translationSetIdentifier}/related-wikis', ListRelatedWikisAction::class)
    ->middleware(['auth.api', 'resolve.actor', 'resolve.account', 'resolve.wiki']);
Route::get('/wiki/{language}/agency/{slug}', GetAgencyWikiAction::class);
Route::get('/wiki/agency/{wikiIdentifier}/draft', GetAgencyDraftWikiAction::class)->middleware('auth.api');
Route::get('/wiki/{language}/agency/{slug}/my/draft', GetMyAgencyDraftWikiAction::class)->middleware(['auth.api', 'resolve.actor', 'resolve.wiki']);
Route::get('/wiki/{language}/group/{slug}', GetGroupWikiAction::class);
Route::get('/wiki/group/{wikiIdentifier}/draft', GetGroupDraftWikiAction::class)->middleware('auth.api');
Route::get('/wiki/{language}/group/{slug}/my/draft', GetMyGroupDraftWikiAction::class)->middleware(['auth.api', 'resolve.actor', 'resolve.wiki']);
Route::get('/wiki/{language}/song/{slug}', GetSongWikiAction::class);
Route::get('/wiki/song/{wikiIdentifier}/draft', GetSongDraftWikiAction::class)->middleware('auth.api');
Route::get('/wiki/{language}/song/{slug}/my/draft', GetMySongDraftWikiAction::class)->middleware(['auth.api', 'resolve.actor', 'resolve.wiki']);
Route::get('/wiki/{language}/talent/{slug}', GetTalentWikiAction::class);
Route::get('/wiki/talent/{wikiIdentifier}/draft', GetTalentDraftWikiAction::class)->middleware('auth.api');
Route::get('/wiki/{language}/talent/{slug}/my/draft', GetMyTalentDraftWikiAction::class)->middleware(['auth.api', 'resolve.actor', 'resolve.wiki']);

// Image
Route::get('/draft-images', ListDraftImagesAction::class)->middleware('auth.api');
Route::get('/images', ListUploadedImagesAction::class)->middleware('auth.api');
Route::middleware(['auth.api', 'resolve.actor', 'resolve.wiki'])->group(function () {
    Route::post('/image/{imageId}/approve', ApproveImageAction::class);
    // Disabled by #605: Route::delete('/image/{imageId}', DeleteImageAction::class);
    Route::post('/image/{imageId}/reject', RejectImageAction::class);
    // Disabled by #605: Route::post('/image/{imageId}/unhide', UnhideImageAction::class);
    Route::post('/image/upload', UploadImageAction::class);
});

// Principal
Route::get('/principal/me', GetCurrentPrincipalAction::class)->middleware(['auth.api', 'resolve.actor', 'resolve.account']);
Route::post('/principal/create', CreatePrincipalAction::class)->middleware(['auth.api', 'resolve.actor']);
Route::middleware('auth.api')->group(function () {
    Route::get('/principal-groups', ListPrincipalGroupsAction::class);
    // Disabled by #605: Route::post('/principal-group/create', CreatePrincipalGroupAction::class);
    // Disabled by #605: Route::post('/principal-group/{principalGroupId}/add-member', AddPrincipalToPrincipalGroupAction::class);
    // Disabled by #605: Route::post('/principal-group/{principalGroupId}/remove-member', RemovePrincipalFromPrincipalGroupAction::class);
    Route::patch('/principal-groups/members', UpdatePrincipalGroupMembersAction::class)
        ->middleware(['resolve.actor', 'resolve.account', 'resolve.wiki']);
    // Disabled by #605: Route::delete('/principal-group/{principalGroupId}', DeletePrincipalGroupAction::class);
    // Disabled by #605: Route::post('/principal-group/{principalGroupId}/attach-role', AttachRoleToPrincipalGroupAction::class);
    // Disabled by #605: Route::post('/principal-group/{principalGroupId}/detach-role', DetachRoleFromPrincipalGroupAction::class);
    // Disabled by #605: Route::post('/role/create', CreateRoleAction::class);
    // Disabled by #605: Route::delete('/role/{roleId}', DeleteRoleAction::class);
    // Disabled by #605: Route::post('/role/{roleId}/attach-policy', AttachPolicyToRoleAction::class);
    // Disabled by #605: Route::post('/role/{roleId}/detach-policy', DetachPolicyFromRoleAction::class);
    // Disabled by #605: Route::post('/policy/create', CreatePolicyAction::class);
    // Disabled by #605: Route::delete('/policy/{policyId}', DeletePolicyAction::class);
});

// ImageDeletionRequest
Route::middleware(['auth.api', 'resolve.actor', 'resolve.wiki'])->group(function () {
    Route::get('/image-deletion-requests', ListImageDeletionRequestsAction::class);
    Route::post('/image/{imageId}/request-deletion', RequestImageDeletionAction::class);
    Route::post('/image/{imageId}/approve-deletion', ApproveImageDeletionAction::class);
    Route::post('/image/{imageId}/reject-deletion', RejectImageDeletionAction::class);
});

// OfficialCertification
Route::get('/official-certifications', ListOfficialCertificationsAction::class)
    ->middleware(['auth.api', 'resolve.actor', 'resolve.wiki']);
Route::get('/my/official-certifications', ListMyOfficialCertificationsAction::class)
    ->middleware(['auth.api', 'resolve.actor', 'resolve.account', 'resolve.wiki']);
Route::post('/official-certification/request', RequestCertificationAction::class)
    ->middleware(['auth.api', 'resolve.actor', 'resolve.account', 'resolve.wiki']);
Route::put('/official-certification/owned-wikis', SyncOwnedWikiCertificationsAction::class)
    ->middleware(['auth.api', 'resolve.actor', 'resolve.account', 'resolve.wiki']);
Route::post('/official-certification/{certificationId}/approve', ApproveCertificationAction::class)
    ->middleware(['auth.api', 'resolve.actor', 'resolve.wiki']);
Route::post('/official-certification/{certificationId}/reject', RejectCertificationAction::class)
    ->middleware(['auth.api', 'resolve.actor', 'resolve.wiki']);

// VideoLink
// Disabled by #605: Route::post('/video-link/save', SaveVideoLinksAction::class)->middleware(['auth.api', 'resolve.actor', 'resolve.wiki']);
