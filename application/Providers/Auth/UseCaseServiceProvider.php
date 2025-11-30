<?php

declare(strict_types=1);

namespace Application\Providers\Auth;

use Illuminate\Support\ServiceProvider;
use Source\Auth\Application\UseCase\Command\Login\Login;
use Source\Auth\Application\UseCase\Command\Login\LoginInterface;
use Source\Auth\Application\UseCase\Command\Logout\Logout;
use Source\Auth\Application\UseCase\Command\Logout\LogoutInterface;
use Source\Auth\Application\UseCase\Command\RegisterUser\RegisterUser;
use Source\Auth\Application\UseCase\Command\RegisterUser\RegisterUserInterface;
use Source\Auth\Application\UseCase\Command\SendAuthCode\SendAuthCode;
use Source\Auth\Application\UseCase\Command\SendAuthCode\SendAuthCodeInterface;
use Source\Auth\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallback;
use Source\Auth\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInterface;
use Source\Auth\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirect;
use Source\Auth\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectInterface;
use Source\Auth\Application\UseCase\Command\VerifyEmail\VerifyEmail;
use Source\Auth\Application\UseCase\Command\VerifyEmail\VerifyEmailInterface;

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
    }
}
