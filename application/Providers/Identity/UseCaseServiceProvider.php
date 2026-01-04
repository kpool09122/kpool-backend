<?php

declare(strict_types=1);

namespace Application\Providers\Identity;

use Illuminate\Support\ServiceProvider;
use Source\Identity\Application\UseCase\Command\Login\Login;
use Source\Identity\Application\UseCase\Command\Login\LoginInterface;
use Source\Identity\Application\UseCase\Command\Logout\Logout;
use Source\Identity\Application\UseCase\Command\Logout\LogoutInterface;
use Source\Identity\Application\UseCase\Command\RegisterUser\RegisterUser;
use Source\Identity\Application\UseCase\Command\RegisterUser\RegisterUserInterface;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCode;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallback;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirect;
use Source\Identity\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectInterface;
use Source\Identity\Application\UseCase\Command\SwitchIdentity\SwitchIdentity;
use Source\Identity\Application\UseCase\Command\SwitchIdentity\SwitchIdentityInterface;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmail;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmailInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(LoginInterface::class, Login::class);
        $this->app->singleton(LogoutInterface::class, Logout::class);
        $this->app->singleton(SendAuthCodeInterface::class, SendAuthCode::class);
        $this->app->singleton(VerifyEmailInterface::class, VerifyEmail::class);
        $this->app->singleton(RegisterUserInterface::class, RegisterUser::class);
        $this->app->singleton(SocialLoginRedirectInterface::class, SocialLoginRedirect::class);
        $this->app->singleton(SocialLoginCallbackInterface::class, SocialLoginCallback::class);
        $this->app->singleton(SwitchIdentityInterface::class, SwitchIdentity::class);
    }
}
