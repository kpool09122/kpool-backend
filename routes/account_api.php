<?php

declare(strict_types=1);

use Application\Http\Action\Account\Account\Command\CreateAccount\CreateAccountAction;
use Application\Http\Action\Account\Account\Command\DeleteAccount\DeleteAccountAction;
use Application\Http\Action\Account\Account\Command\Documents\UploadDocumentsAction;
use Application\Http\Action\Account\Account\Command\UpdateAccount\UpdateAccountAction;
use Application\Http\Action\Account\Account\Query\GetAccount\GetAccountAction;
use Application\Http\Action\Account\Account\Query\ListMyAccountDocuments\ListMyAccountDocumentsAction;
use Application\Http\Action\Account\Account\Query\ViewMyAccountDocument\ViewMyAccountDocumentAction;
use Application\Http\Action\Account\Account\AccountVerification\Command\ApproveVerification\ApproveVerificationAction;
use Application\Http\Action\Account\Account\AccountVerification\Command\RejectVerification\RejectVerificationAction;
use Application\Http\Action\Account\Account\AccountVerification\Command\RequestVerification\RequestVerificationAction;
use Application\Http\Action\Account\Member\Query\ListMembers\ListMembersAction;
use Application\Http\Action\Account\Affiliation\Command\ApproveAffiliation\ApproveAffiliationAction;
use Application\Http\Action\Account\Affiliation\Command\RejectAffiliation\RejectAffiliationAction;
use Application\Http\Action\Account\Affiliation\Command\RequestAffiliation\RequestAffiliationAction;
use Application\Http\Action\Account\Affiliation\Command\TerminateAffiliation\TerminateAffiliationAction;
use Application\Http\Action\Account\Delegation\Command\ApproveDelegation\ApproveDelegationAction;
use Application\Http\Action\Account\Delegation\Command\RequestDelegation\RequestDelegationAction;
use Application\Http\Action\Account\Delegation\Command\RevokeDelegation\RevokeDelegationAction;
use Application\Http\Action\Account\DelegationPermission\Command\GrantDelegationPermission\GrantDelegationPermissionAction;
use Application\Http\Action\Account\DelegationPermission\Command\RevokeDelegationPermission\RevokeDelegationPermissionAction;
use Application\Http\Action\Account\PrincipalGroup\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroupAction;
use Application\Http\Action\Account\PrincipalGroup\Command\CreatePrincipalGroup\CreatePrincipalGroupAction;
use Application\Http\Action\Account\PrincipalGroup\Command\DeletePrincipalGroup\DeletePrincipalGroupAction;
use Application\Http\Action\Account\PrincipalGroup\Query\ListPrincipalGroups\ListPrincipalGroupsAction;
use Application\Http\Action\Account\PrincipalGroup\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupAction;
use Application\Http\Action\Account\PrincipalGroup\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersAction;
use Application\Http\Action\Account\Invitation\Command\InviteMember\InviteMemberAction;
use Illuminate\Support\Facades\Route;

// Account
Route::post('/accounts', CreateAccountAction::class);

Route::middleware(['auth.api', 'resolve.actor', 'resolve.account'])->group(function () {
    // Account
    Route::get('/my/documents', ListMyAccountDocumentsAction::class);
    Route::get('/my/documents/{documentType}', ViewMyAccountDocumentAction::class);
    Route::get('/accounts/{accountId}', GetAccountAction::class);
    Route::patch('/accounts/{accountId}', UpdateAccountAction::class);
    Route::delete('/accounts/{accountId}', DeleteAccountAction::class);
    Route::post('/accounts/{accountId}/documents', UploadDocumentsAction::class);

    // Delegation
    Route::post('/delegations', RequestDelegationAction::class);
    Route::post('/delegations/{delegationId}/approve', ApproveDelegationAction::class);
    Route::post('/delegations/{delegationId}/revoke', RevokeDelegationAction::class);

    // DelegationPermission
    Route::post('/delegation-permissions', GrantDelegationPermissionAction::class);
    Route::delete('/delegation-permissions/{delegationPermissionId}', RevokeDelegationPermissionAction::class);

    // Member
    Route::get('/members', ListMembersAction::class);

    // PrincipalGroup
    Route::get('/principal-groups', ListPrincipalGroupsAction::class);
    Route::post('/principal-groups', CreatePrincipalGroupAction::class);
    Route::post('/principal-groups/{principalGroupId}/add-member', AddPrincipalToPrincipalGroupAction::class);
    Route::post('/principal-groups/{principalGroupId}/remove-member', RemovePrincipalFromPrincipalGroupAction::class);
    Route::patch('/principal-groups/members', UpdatePrincipalGroupMembersAction::class);
    Route::delete('/principal-groups/{principalGroupId}', DeletePrincipalGroupAction::class);

    // Invitation
    Route::post('/invitations', InviteMemberAction::class);

    // AccountVerification
    Route::post('/account-verifications', RequestVerificationAction::class);
    Route::post('/account-verifications/{verificationId}/approve', ApproveVerificationAction::class);
    Route::post('/account-verifications/{verificationId}/reject', RejectVerificationAction::class);

    // Affiliation
    Route::post('/affiliations', RequestAffiliationAction::class);
    Route::post('/affiliations/{affiliationId}/approve', ApproveAffiliationAction::class);
    Route::post('/affiliations/{affiliationId}/reject', RejectAffiliationAction::class);
    Route::post('/affiliations/{affiliationId}/terminate', TerminateAffiliationAction::class);
});
