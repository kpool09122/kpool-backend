<?php

declare(strict_types=1);

use Application\Http\Action\Identity\Command\Logout\LogoutAction;
use Application\Http\Action\Identity\Command\Passkey\PasskeyAction;
use Application\Http\Action\Identity\Command\SendAuthCode\SendAuthCodeAction;
use Application\Http\Action\Identity\Command\SocialLogin\Callback\SocialLoginCallbackAction;
use Application\Http\Action\Identity\Command\SocialLogin\Redirect\SocialLoginRedirectAction;
use Application\Http\Action\Identity\Command\UpdateIdentity\UpdateIdentityAction;
use Application\Http\Action\Identity\Command\VerifyEmail\VerifyEmailAction;
use Application\Http\Action\Identity\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityAction;
use Illuminate\Support\Facades\Route;

// Public Auth
Route::post('/auth/send-auth-code', SendAuthCodeAction::class);
Route::post('/auth/verify-email', VerifyEmailAction::class);
Route::post('/auth/passkey/register/options', [PasskeyAction::class, 'beginSignup']);
Route::post('/auth/passkey/register/verify', [PasskeyAction::class, 'finishSignup']);
Route::post('/auth/passkey/login/options', [PasskeyAction::class, 'beginLogin']);
Route::post('/auth/passkey/login/verify', [PasskeyAction::class, 'finishLogin']);

// Social Login (public)
Route::get('/auth/social/{provider}/redirect', SocialLoginRedirectAction::class);
Route::get('/auth/social/{provider}/callback', SocialLoginCallbackAction::class);

// Authenticated
Route::middleware(['auth.api', 'resolve.actor'])->group(function () {
    Route::get('/auth/me', GetAuthenticatedIdentityAction::class);
    Route::post('/auth/logout', LogoutAction::class);
    Route::get('/auth/passkeys', [PasskeyAction::class, 'list']);
    Route::post('/auth/passkeys/options', [PasskeyAction::class, 'beginAdd']);
    Route::post('/auth/passkeys/verify', [PasskeyAction::class, 'finishAdd']);
    Route::patch('/auth/passkeys/{passkeyIdentifier}', [PasskeyAction::class, 'rename']);
    Route::delete('/auth/passkeys/{passkeyIdentifier}', [PasskeyAction::class, 'delete']);

    Route::patch('/identities/me', UpdateIdentityAction::class);
});
