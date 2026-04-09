<?php

declare(strict_types=1);

use Application\Http\Action\Account\Account\Command\CreateAccount\CreateAccountAction;
use Application\Http\Action\Account\Account\Command\DeleteAccount\DeleteAccountAction;
use Application\Http\Action\Account\IdentityGroup\Command\AddIdentityToIdentityGroup\AddIdentityToIdentityGroupAction;
use Application\Http\Action\Account\IdentityGroup\Command\CreateIdentityGroup\CreateIdentityGroupAction;
use Application\Http\Action\Account\IdentityGroup\Command\DeleteIdentityGroup\DeleteIdentityGroupAction;
use Application\Http\Action\Account\IdentityGroup\Command\RemoveIdentityFromIdentityGroup\RemoveIdentityFromIdentityGroupAction;
use Illuminate\Support\Facades\Route;

// Account
Route::post('/accounts', CreateAccountAction::class);
Route::delete('/accounts/{accountId}', DeleteAccountAction::class);

// IdentityGroup
Route::post('/identity-groups', CreateIdentityGroupAction::class);
Route::post('/identity-groups/{identityGroupId}/add-member', AddIdentityToIdentityGroupAction::class);
Route::post('/identity-groups/{identityGroupId}/remove-member', RemoveIdentityFromIdentityGroupAction::class);
Route::delete('/identity-groups/{identityGroupId}', DeleteIdentityGroupAction::class);
