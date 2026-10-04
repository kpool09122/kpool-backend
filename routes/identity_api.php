<?php

declare(strict_types=1);

use Application\Http\Action\Identity\Command\AddPasskey\AddPasskeyAction;
use Application\Http\Action\Identity\Command\AuthenticateWithPasskey\AuthenticateWithPasskeyAction;
use Application\Http\Action\Identity\Command\CompleteStepUpWithPasskey\CompleteStepUpWithPasskeyAction;
use Application\Http\Action\Identity\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptionsAction;
use Application\Http\Action\Identity\Command\CreatePasskeyOptions\CreatePasskeyOptionsAction;
use Application\Http\Action\Identity\Command\CreatePasskeyRecoveryOptions\CreatePasskeyRecoveryOptionsAction;
use Application\Http\Action\Identity\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsAction;
use Application\Http\Action\Identity\Command\CreateStepUpPasskeyOptions\CreateStepUpPasskeyOptionsAction;
use Application\Http\Action\Identity\Command\DeletePasskey\DeletePasskeyAction;
use Application\Http\Action\Identity\Command\Logout\LogoutAction;
use Application\Http\Action\Identity\Command\RecoverPasskey\RecoverPasskeyAction;
use Application\Http\Action\Identity\Command\RegisterWithPasskey\RegisterWithPasskeyAction;
use Application\Http\Action\Identity\Command\SendAuthCode\SendAuthCodeAction;
use Application\Http\Action\Identity\Command\SendPasskeyRecoveryEmail\SendPasskeyRecoveryEmailAction;
use Application\Http\Action\Identity\Command\SendSocialLinkingEmail\SendSocialLinkingEmailAction;
use Application\Http\Action\Identity\Command\SocialAuthenticate\Callback\SocialAuthenticateCallbackAction;
use Application\Http\Action\Identity\Command\SocialLogin\Redirect\SocialLoginRedirectAction;
use Application\Http\Action\Identity\Command\StartPasskeyRecoveryWithSocial\StartPasskeyRecoveryWithSocialAction;
use Application\Http\Action\Identity\Command\StartStepUpWithSocial\StartStepUpWithSocialAction;
use Application\Http\Action\Identity\Command\UpdateIdentity\UpdateIdentityAction;
use Application\Http\Action\Identity\Command\UpdatePasskey\UpdatePasskeyAction;
use Application\Http\Action\Identity\Command\VerifyEmail\VerifyEmailAction;
use Application\Http\Action\Identity\Command\VerifyPasskeyRecoveryEmail\VerifyPasskeyRecoveryEmailAction;
use Application\Http\Action\Identity\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailAction;
use Application\Http\Action\Identity\Command\WithdrawFromService\WithdrawFromServiceAction;
use Application\Http\Action\Identity\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityAction;
use Application\Http\Action\Identity\Query\GetCsrfToken\GetCsrfTokenAction;
use Application\Http\Action\Identity\Query\GetSocialLinking\GetSocialLinkingAction;
use Application\Http\Action\Identity\Query\GetWithdrawalEligibility\GetWithdrawalEligibilityAction;
use Application\Http\Action\Identity\Query\ListPasskeys\ListPasskeysAction;
use Illuminate\Support\Facades\Route;

Route::middleware('rate-limit:screen,query')->group(function () {
    Route::get('/auth/csrf-token', GetCsrfTokenAction::class);

    // SSO linking is authorized by the originating session and its dedicated email code.
    Route::get('/auth/social/link', GetSocialLinkingAction::class);
});

Route::middleware('rate-limit:screen,command')->group(function () {
    // Public Auth
    Route::post('/auth/send-auth-code', SendAuthCodeAction::class);
    Route::post('/auth/verify-email', VerifyEmailAction::class);
    Route::post('/auth/passkeys/registration/options', CreatePasskeyRegistrationOptionsAction::class);
    Route::post('/auth/passkeys/registration', RegisterWithPasskeyAction::class);
    Route::post('/auth/passkeys/authentication/options', CreatePasskeyAuthenticationOptionsAction::class);
    Route::post('/auth/passkeys/authentication', AuthenticateWithPasskeyAction::class);
    Route::post('/auth/passkeys/recovery/email', SendPasskeyRecoveryEmailAction::class);
    Route::post('/auth/passkeys/recovery/email/verification', VerifyPasskeyRecoveryEmailAction::class);
    Route::get('/auth/passkeys/recovery/social/{provider}/redirect', StartPasskeyRecoveryWithSocialAction::class);
    Route::post('/auth/passkeys/recovery/options', CreatePasskeyRecoveryOptionsAction::class);
    Route::post('/auth/passkeys/recovery', RecoverPasskeyAction::class);

    // SSO linking is authorized by the originating session and its dedicated email code.
    Route::post('/auth/social/link/email', SendSocialLinkingEmailAction::class);
    Route::post('/auth/social/link/email/verification', VerifySocialLinkingEmailAction::class);

    // Social authentication (public)
    Route::get('/auth/social/{provider}/redirect', SocialLoginRedirectAction::class);
    Route::get('/auth/social/{provider}/callback', SocialAuthenticateCallbackAction::class);
});

Route::middleware(['auth.api', 'resolve.actor'])->group(function () {
    Route::middleware('rate-limit:screen,query')->group(function () {
        Route::get('/auth/me', GetAuthenticatedIdentityAction::class);
        Route::get('/auth/passkeys', ListPasskeysAction::class);
        // Disabled by #605: Route::get('/auth/identities/{identityIdentifier}/profile', GetIdentityProfileAction::class);
        Route::get('/identities/me/withdrawal-eligibility', GetWithdrawalEligibilityAction::class);
    });

    Route::middleware('rate-limit:screen,command')->group(function () {
        Route::post('/auth/step-up/passkey/options', CreateStepUpPasskeyOptionsAction::class);
        Route::post('/auth/step-up/passkey', CompleteStepUpWithPasskeyAction::class);
        Route::get('/auth/step-up/social/{provider}/redirect', StartStepUpWithSocialAction::class);
        Route::post('/auth/passkeys/addition/options', CreatePasskeyOptionsAction::class);
        Route::post('/auth/passkeys/addition', AddPasskeyAction::class);
        Route::patch('/auth/passkeys/{passkeyIdentifier}', UpdatePasskeyAction::class);
        Route::delete('/auth/passkeys/{passkeyIdentifier}', DeletePasskeyAction::class);
        Route::post('/auth/logout', LogoutAction::class);
        Route::patch('/identities/me', UpdateIdentityAction::class);
        Route::delete('/identities/me', WithdrawFromServiceAction::class);
    });
});
