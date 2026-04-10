<?php

declare(strict_types=1);

use Application\Http\Action\Account\Account\Command\CreateAccount\CreateAccountAction;
use Application\Http\Action\Account\Account\Command\DeleteAccount\DeleteAccountAction;
use Application\Http\Action\Account\Delegation\Command\ApproveDelegation\ApproveDelegationAction;
use Application\Http\Action\Account\Delegation\Command\RequestDelegation\RequestDelegationAction;
use Application\Http\Action\Account\Delegation\Command\RevokeDelegation\RevokeDelegationAction;
use Application\Http\Action\Account\DelegationPermission\Command\GrantDelegationPermission\GrantDelegationPermissionAction;
use Application\Http\Action\Account\DelegationPermission\Command\RevokeDelegationPermission\RevokeDelegationPermissionAction;
use Application\Http\Action\Account\IdentityGroup\Command\AddIdentityToIdentityGroup\AddIdentityToIdentityGroupAction;
use Application\Http\Action\Account\IdentityGroup\Command\CreateIdentityGroup\CreateIdentityGroupAction;
use Application\Http\Action\Account\IdentityGroup\Command\DeleteIdentityGroup\DeleteIdentityGroupAction;
use Application\Http\Action\Account\IdentityGroup\Command\RemoveIdentityFromIdentityGroup\RemoveIdentityFromIdentityGroupAction;
use Illuminate\Support\Facades\Route;

// Account
Route::post('/accounts', CreateAccountAction::class);
Route::delete('/accounts/{accountId}', DeleteAccountAction::class);

// Delegation
Route::post('/delegations', RequestDelegationAction::class);
Route::post('/delegations/{delegationId}/approve', ApproveDelegationAction::class);
Route::post('/delegations/{delegationId}/revoke', RevokeDelegationAction::class);

// DelegationPermission
Route::post('/delegation-permissions', GrantDelegationPermissionAction::class);
Route::delete('/delegation-permissions/{delegationPermissionId}', RevokeDelegationPermissionAction::class);

// IdentityGroup
Route::post('/identity-groups', CreateIdentityGroupAction::class);
Route::post('/identity-groups/{identityGroupId}/add-member', AddIdentityToIdentityGroupAction::class);
Route::post('/identity-groups/{identityGroupId}/remove-member', RemoveIdentityFromIdentityGroupAction::class);
Route::delete('/identity-groups/{identityGroupId}', DeleteIdentityGroupAction::class);
