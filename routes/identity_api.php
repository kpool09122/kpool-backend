<?php

declare(strict_types=1);

use Application\Http\Action\Identity\Command\CreateIdentity\CreateIdentityAction;
use Application\Http\Action\Identity\Command\Login\LoginAction;
use Application\Http\Action\Identity\Command\Logout\LogoutAction;
use Application\Http\Action\Identity\Command\SendAuthCode\SendAuthCodeAction;
use Application\Http\Action\Identity\Command\SocialLogin\Callback\SocialLoginCallbackAction;
use Application\Http\Action\Identity\Command\SocialLogin\Redirect\SocialLoginRedirectAction;
use Application\Http\Action\Identity\Command\SwitchIdentity\SwitchIdentityAction;
use Application\Http\Action\Identity\Command\VerifyEmail\VerifyEmailAction;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/auth/send-auth-code', SendAuthCodeAction::class);
Route::post('/auth/verify-email', VerifyEmailAction::class);
Route::post('/auth/register', CreateIdentityAction::class);
Route::post('/auth/login', LoginAction::class);
Route::post('/auth/logout', LogoutAction::class);
Route::post('/auth/switch-identity', SwitchIdentityAction::class);

// Social Login
Route::get('/auth/social/{provider}/redirect', SocialLoginRedirectAction::class);
Route::get('/auth/social/{provider}/callback', SocialLoginCallbackAction::class);
