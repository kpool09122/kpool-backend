<?php

declare(strict_types=1);

namespace Application\Providers\Identity;

use Illuminate\Support\ServiceProvider;
use Source\Identity\Application\UseCase\Command\Logout\Logout;
use Source\Identity\Application\UseCase\Command\Logout\LogoutInterface;
use Source\Identity\Application\UseCase\Command\Passkey\PasskeyUseCase;
use Source\Identity\Application\UseCase\Command\Passkey\PasskeyUseCaseInterface;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCode;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallback;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirect;
use Source\Identity\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectInterface;
use Source\Identity\Application\UseCase\Command\UpdateIdentity\UpdateIdentity;
use Source\Identity\Application\UseCase\Command\UpdateIdentity\UpdateIdentityInterface;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmail;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmailInterface;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInterface;
use Source\Identity\Application\UseCase\Query\GetIdentityProfile\GetIdentityProfileInterface;
use Source\Identity\Infrastructure\Query\GetAuthenticatedIdentity;
use Source\Identity\Infrastructure\Query\GetIdentityProfile;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(PasskeyUseCaseInterface::class, PasskeyUseCase::class);
        $this->app->singleton(LogoutInterface::class, Logout::class);
        $this->app->singleton(SendAuthCodeInterface::class, SendAuthCode::class);
        $this->app->singleton(VerifyEmailInterface::class, VerifyEmail::class);

        $this->app->singleton(SocialLoginRedirectInterface::class, SocialLoginRedirect::class);
        $this->app->singleton(SocialLoginCallbackInterface::class, SocialLoginCallback::class);

        $this->app->singleton(UpdateIdentityInterface::class, UpdateIdentity::class);
        $this->app->singleton(GetAuthenticatedIdentityInterface::class, GetAuthenticatedIdentity::class);
        $this->app->singleton(GetIdentityProfileInterface::class, GetIdentityProfile::class);
    }
}
