<?php

declare(strict_types=1);

namespace Application\Providers\Identity;

use Illuminate\Support\ServiceProvider;
use Source\Identity\Application\UseCase\Command\AddPasskey\AddPasskey;
use Source\Identity\Application\UseCase\Command\AddPasskey\AddPasskeyInterface;
use Source\Identity\Application\UseCase\Command\AddPasskeyOptions\AddPasskeyOptions;
use Source\Identity\Application\UseCase\Command\AddPasskeyOptions\AddPasskeyOptionsInterface;
use Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey\AuthenticateWithPasskey;
use Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey\AuthenticateWithPasskeyInterface;
use Source\Identity\Application\UseCase\Command\CreateIdentity\CreateIdentity;
use Source\Identity\Application\UseCase\Command\CreateIdentity\CreateIdentityInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptions;
use Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptionsInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptions;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsInterface;
use Source\Identity\Application\UseCase\Command\Login\Login;
use Source\Identity\Application\UseCase\Command\Login\LoginInterface;
use Source\Identity\Application\UseCase\Command\Logout\Logout;
use Source\Identity\Application\UseCase\Command\Logout\LogoutInterface;
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
use Source\Identity\Application\UseCase\Query\ListPasskeys\ListPasskeysInterface;
use Source\Identity\Infrastructure\Query\GetAuthenticatedIdentity;
use Source\Identity\Infrastructure\Query\GetIdentityProfile;
use Source\Identity\Infrastructure\Query\ListPasskeys;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AddPasskeyInterface::class, AddPasskey::class);
        $this->app->singleton(AddPasskeyOptionsInterface::class, AddPasskeyOptions::class);
        $this->app->singleton(AuthenticateWithPasskeyInterface::class, AuthenticateWithPasskey::class);
        $this->app->singleton(LoginInterface::class, Login::class);
        $this->app->singleton(LogoutInterface::class, Logout::class);
        $this->app->singleton(SendAuthCodeInterface::class, SendAuthCode::class);
        $this->app->singleton(VerifyEmailInterface::class, VerifyEmail::class);
        $this->app->singleton(CreateIdentityInterface::class, CreateIdentity::class);
        $this->app->singleton(
            CreatePasskeyRegistrationOptionsInterface::class,
            CreatePasskeyRegistrationOptions::class,
        );
        $this->app->singleton(
            CreatePasskeyAuthenticationOptionsInterface::class,
            CreatePasskeyAuthenticationOptions::class,
        );
        $this->app->singleton(SocialLoginRedirectInterface::class, SocialLoginRedirect::class);
        $this->app->singleton(SocialLoginCallbackInterface::class, SocialLoginCallback::class);

        $this->app->singleton(UpdateIdentityInterface::class, UpdateIdentity::class);
        $this->app->singleton(GetAuthenticatedIdentityInterface::class, GetAuthenticatedIdentity::class);
        $this->app->singleton(GetIdentityProfileInterface::class, GetIdentityProfile::class);
        $this->app->singleton(ListPasskeysInterface::class, ListPasskeys::class);
    }
}
