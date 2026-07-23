<?php

declare(strict_types=1);

use Application\Http\Action\Account\Account\Command\CreateAccount\CreateAccountAction;
use Application\Http\Action\Account\Account\Command\DeleteAccount\DeleteAccountAction;
use Application\Http\Action\Account\Account\AccountVerification\Command\ApproveVerification\ApproveVerificationAction;
use Application\Http\Action\Account\Account\AccountVerification\Command\RejectVerification\RejectVerificationAction;
use Application\Http\Action\Account\Account\AccountVerification\Command\RequestVerification\RequestVerificationAction;
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
use Application\Http\Action\Account\PrincipalGroup\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupAction;
use Application\Http\Action\Account\Invitation\Command\CreateInvitation\CreateInvitationAction;
use Illuminate\Support\Facades\Route;

// Account
Route::post('/accounts', CreateAccountAction::class);

Route::middleware(['auth.api', 'resolve.actor'])->group(function () {
    // Account
    Route::delete('/accounts/{accountId}', DeleteAccountAction::class);

    // Delegation
    Route::post('/delegations', RequestDelegationAction::class);
    Route::post('/delegations/{delegationId}/approve', ApproveDelegationAction::class);
    Route::post('/delegations/{delegationId}/revoke', RevokeDelegationAction::class);

    // DelegationPermission
    Route::post('/delegation-permissions', GrantDelegationPermissionAction::class);
    Route::delete('/delegation-permissions/{delegationPermissionId}', RevokeDelegationPermissionAction::class);

    // PrincipalGroup
    Route::post('/principal-groups', CreatePrincipalGroupAction::class);
    Route::post('/principal-groups/{principalGroupId}/add-member', AddPrincipalToPrincipalGroupAction::class);
    Route::post('/principal-groups/{principalGroupId}/remove-member', RemovePrincipalFromPrincipalGroupAction::class);
    Route::delete('/principal-groups/{principalGroupId}', DeletePrincipalGroupAction::class);

    // Invitation
    Route::post('/invitations', CreateInvitationAction::class);

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
