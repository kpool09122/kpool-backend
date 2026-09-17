<?php

declare(strict_types=1);

use Application\Http\Action\Account\Account\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestAction;
use Application\Http\Action\Account\Account\Command\CreateAccount\CreateAccountAction;
use Application\Http\Action\Account\Account\Command\RejectAccountCategoryChangeRequest\RejectAccountCategoryChangeRequestAction;
use Application\Http\Action\Account\Account\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeAction;
use Application\Http\Action\Account\Account\Command\SwitchAccount\SwitchAccountAction;
use Application\Http\Action\Account\Account\Command\UpdateAccount\UpdateAccountAction;
use Application\Http\Action\Account\Account\Command\UploadDocuments\UploadDocumentsAction;
use Application\Http\Action\Account\Account\Query\GetAccount\GetAccountAction;
use Application\Http\Action\Account\Account\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestAction;
use Application\Http\Action\Account\Account\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsAction;
use Application\Http\Action\Account\Account\Query\ListMyAccountDocuments\ListMyAccountDocumentsAction;
use Application\Http\Action\Account\Account\Query\ViewAccountDocument\ViewAccountDocumentAction;
use Application\Http\Action\Account\Account\Query\ViewMyAccountDocument\ViewMyAccountDocumentAction;
use Application\Http\Action\Account\Affiliation\Command\ApproveAffiliation\ApproveAffiliationAction;
use Application\Http\Action\Account\Affiliation\Command\RejectAffiliation\RejectAffiliationAction;
use Application\Http\Action\Account\Affiliation\Command\RequestAffiliation\RequestAffiliationAction;
use Application\Http\Action\Account\Affiliation\Query\ListAffiliations\ListAffiliationsAction;
use Application\Http\Action\Account\Delegation\Command\ApproveDelegation\ApproveDelegationAction;
use Application\Http\Action\Account\Delegation\Command\RejectDelegation\RejectDelegationAction;
use Application\Http\Action\Account\Delegation\Command\RequestDelegation\RequestDelegationAction;
use Application\Http\Action\Account\Delegation\Query\ListDelegations\ListDelegationsAction;
use Application\Http\Action\Account\Invitation\Command\InviteMember\InviteMemberAction;
use Application\Http\Action\Account\Member\Query\ListMembers\ListMembersAction;
use Application\Http\Action\Account\PrincipalGroup\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersAction;
use Application\Http\Action\Account\PrincipalGroup\Query\ListPrincipalGroups\ListPrincipalGroupsAction;
use Illuminate\Support\Facades\Route;

// Account
Route::post('/accounts', CreateAccountAction::class);

Route::middleware(['auth.api', 'resolve.actor', 'resolve.account'])->group(function () {
    // Account
    Route::get('/my/documents', ListMyAccountDocumentsAction::class);
    Route::get('/my/documents/{documentType}', ViewMyAccountDocumentAction::class);
    Route::get('/accounts/{accountId}', GetAccountAction::class);
    Route::patch('/accounts/{accountId}', UpdateAccountAction::class);
    // Disabled by #605: Route::delete('/accounts/{accountId}', DeleteAccountAction::class);
    Route::post('/accounts/switch', SwitchAccountAction::class);
    Route::post('/accounts/{accountId}/documents', UploadDocumentsAction::class);
    Route::get('/accounts/{accountId}/documents/{documentType}', ViewAccountDocumentAction::class);

    // Delegation
    Route::get('/delegations', ListDelegationsAction::class);
    Route::post('/delegations', RequestDelegationAction::class);
    Route::post('/delegations/{delegationId}/approve', ApproveDelegationAction::class);
    Route::post('/delegations/{delegationId}/reject', RejectDelegationAction::class);

    // Member
    Route::get('/members', ListMembersAction::class);

    // PrincipalGroup
    Route::get('/principal-groups', ListPrincipalGroupsAction::class);
    // Disabled by #605: Route::post('/principal-groups', CreatePrincipalGroupAction::class);
    // Disabled by #605: Route::post('/principal-groups/{principalGroupId}/add-member', AddPrincipalToPrincipalGroupAction::class);
    // Disabled by #605: Route::post('/principal-groups/{principalGroupId}/remove-member', RemovePrincipalFromPrincipalGroupAction::class);
    Route::patch('/principal-groups/members', UpdatePrincipalGroupMembersAction::class);
    // Disabled by #605: Route::delete('/principal-groups/{principalGroupId}', DeletePrincipalGroupAction::class);

    // Invitation
    Route::post('/invitations', InviteMemberAction::class);

    // AccountCategoryChangeRequest
    Route::get('/account-category-change-requests', ListAccountCategoryChangeRequestsAction::class);
    Route::get('/account-category-change-requests/{requestId}', GetAccountCategoryChangeRequestAction::class);
    Route::post('/accounts/{accountIdentifier}/category-change-requests', RequestAccountCategoryChangeAction::class);
    Route::post('/account-category-change-requests/{requestId}/approve', ApproveAccountCategoryChangeRequestAction::class);
    Route::post('/account-category-change-requests/{requestId}/reject', RejectAccountCategoryChangeRequestAction::class);

    // Affiliation
    Route::get('/affiliations', ListAffiliationsAction::class);
    Route::post('/affiliations', RequestAffiliationAction::class);
    Route::post('/affiliations/{affiliationId}/approve', ApproveAffiliationAction::class);
    Route::post('/affiliations/{affiliationId}/reject', RejectAffiliationAction::class);
    // Disabled by #605: Route::post('/affiliations/{affiliationId}/terminate', TerminateAffiliationAction::class);
});
