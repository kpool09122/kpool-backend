<?php

declare(strict_types=1);

use Application\Http\Action\Identity\Command\CreateIdentity\CreateIdentityAction;
use Application\Http\Action\Identity\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptionsAction;
use Application\Http\Action\Identity\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsAction;
use Application\Http\Action\Identity\Command\Login\LoginAction;
use Application\Http\Action\Identity\Command\Logout\LogoutAction;
use Application\Http\Action\Identity\Command\SocialLogin\Callback\SocialLoginCallbackAction;
use Application\Http\Action\Identity\Command\SocialLogin\Redirect\SocialLoginRedirectAction;
use Application\Http\Action\Identity\Command\UpdateIdentity\UpdateIdentityAction;
use Application\Http\Action\Identity\Command\VerifyEmail\VerifyEmailAction;
use Application\Http\Action\Identity\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityAction;
use Illuminate\Support\Facades\Route;

// Public Auth
// Disabled by #605: Route::post('/auth/send-auth-code', SendAuthCodeAction::class);
Route::post('/auth/verify-email', VerifyEmailAction::class);
Route::post('/auth/register', CreateIdentityAction::class);
Route::post('/auth/passkeys/registration/options', CreatePasskeyRegistrationOptionsAction::class);
Route::post('/auth/passkeys/authentication/options', CreatePasskeyAuthenticationOptionsAction::class);
Route::post('/auth/login', LoginAction::class);

// Social Login (public)
Route::get('/auth/social/{provider}/redirect', SocialLoginRedirectAction::class);
Route::get('/auth/social/{provider}/callback', SocialLoginCallbackAction::class);

// Authenticated
Route::middleware(['auth.api', 'resolve.actor'])->group(function () {
    Route::get('/auth/me', GetAuthenticatedIdentityAction::class);
    // Disabled by #605: Route::get('/auth/identities/{identityIdentifier}/profile', GetIdentityProfileAction::class);
    Route::post('/auth/logout', LogoutAction::class);

    Route::patch('/identities/me', UpdateIdentityAction::class);
});
