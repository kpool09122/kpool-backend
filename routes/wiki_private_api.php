<?php

declare(strict_types=1);

use Application\Http\Action\Wiki\Image\Command\ApproveImage\ApproveImageAction;
use Application\Http\Action\Wiki\Image\Command\DeleteImage\DeleteImageAction;
use Application\Http\Action\Wiki\Image\Command\RejectImage\RejectImageAction;
use Application\Http\Action\Wiki\Image\Command\UnhideImage\UnhideImageAction;
use Application\Http\Action\Wiki\Image\Command\UploadImage\UploadImageAction;
use Application\Http\Action\Wiki\Principal\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroupAction;
use Application\Http\Action\Wiki\Principal\Command\AttachPolicyToRole\AttachPolicyToRoleAction;
use Application\Http\Action\Wiki\Principal\Command\AttachRoleToPrincipalGroup\AttachRoleToPrincipalGroupAction;
use Application\Http\Action\Wiki\Principal\Command\CreatePolicy\CreatePolicyAction;
use Application\Http\Action\Wiki\OfficialCertification\Command\ApproveCertification\ApproveCertificationAction;
use Application\Http\Action\Wiki\OfficialCertification\Command\RejectCertification\RejectCertificationAction;
use Application\Http\Action\Wiki\OfficialCertification\Command\RequestCertification\RequestCertificationAction;
use Application\Http\Action\Wiki\Principal\Command\CreatePrincipal\CreatePrincipalAction;
use Application\Http\Action\Wiki\Principal\Command\CreatePrincipalGroup\CreatePrincipalGroupAction;
use Application\Http\Action\Wiki\Principal\Command\CreateRole\CreateRoleAction;
use Application\Http\Action\Wiki\Principal\Command\DeletePolicy\DeletePolicyAction;
use Application\Http\Action\Wiki\Principal\Command\DeletePrincipalGroup\DeletePrincipalGroupAction;
use Application\Http\Action\Wiki\Principal\Command\DeleteRole\DeleteRoleAction;
use Application\Http\Action\Wiki\Principal\Command\DetachPolicyFromRole\DetachPolicyFromRoleAction;
use Application\Http\Action\Wiki\Principal\Command\DetachRoleFromPrincipalGroup\DetachRoleFromPrincipalGroupAction;
use Application\Http\Action\Wiki\Principal\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupAction;
use Application\Http\Action\Wiki\Wiki\Command\ApproveWiki\ApproveWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\AutoCreateWiki\AutoCreateWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\CreateWiki\CreateWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\EditWiki\EditWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\MergeWiki\MergeWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\PublishWiki\PublishWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\RejectWiki\RejectWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\RollbackWiki\RollbackWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\SubmitWiki\SubmitWikiAction;
use Application\Http\Action\Wiki\ImageHideRequest\Command\ApproveImageHideRequest\ApproveImageHideRequestAction;
use Application\Http\Action\Wiki\ImageHideRequest\Command\RejectImageHideRequest\RejectImageHideRequestAction;
use Application\Http\Action\Wiki\ImageHideRequest\Command\RequestImageHide\RequestImageHideAction;
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

// Image
Route::post('/image/{imageId}/approve', ApproveImageAction::class);
Route::delete('/image/{imageId}', DeleteImageAction::class);
Route::post('/image/{imageId}/reject', RejectImageAction::class);
Route::post('/image/{imageId}/unhide', UnhideImageAction::class);
Route::post('/image/upload', UploadImageAction::class);

// Principal
Route::post('/principal/create', CreatePrincipalAction::class);
Route::post('/principal-group/create', CreatePrincipalGroupAction::class);
Route::post('/principal-group/{principalGroupId}/add-member', AddPrincipalToPrincipalGroupAction::class);
Route::post('/principal-group/{principalGroupId}/remove-member', RemovePrincipalFromPrincipalGroupAction::class);
Route::delete('/principal-group/{principalGroupId}', DeletePrincipalGroupAction::class);
Route::post('/principal-group/{principalGroupId}/attach-role', AttachRoleToPrincipalGroupAction::class);
Route::post('/principal-group/{principalGroupId}/detach-role', DetachRoleFromPrincipalGroupAction::class);
Route::post('/role/create', CreateRoleAction::class);
Route::delete('/role/{roleId}', DeleteRoleAction::class);
Route::post('/role/{roleId}/attach-policy', AttachPolicyToRoleAction::class);
Route::post('/role/{roleId}/detach-policy', DetachPolicyFromRoleAction::class);
Route::post('/policy/create', CreatePolicyAction::class);
Route::delete('/policy/{policyId}', DeletePolicyAction::class);

// ImageHideRequest
Route::post('/image-hide-request/create', RequestImageHideAction::class);
Route::post('/image-hide-request/{requestId}/approve', ApproveImageHideRequestAction::class);
Route::post('/image-hide-request/{requestId}/reject', RejectImageHideRequestAction::class);

// OfficialCertification
Route::post('/official-certification/request', RequestCertificationAction::class);
Route::post('/official-certification/{certificationId}/approve', ApproveCertificationAction::class);
Route::post('/official-certification/{certificationId}/reject', RejectCertificationAction::class);
