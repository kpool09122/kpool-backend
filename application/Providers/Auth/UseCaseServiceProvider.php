<?php

declare(strict_types=1);

namespace Application\Providers\Auth;

use Illuminate\Support\ServiceProvider;
use Source\Auth\Application\UseCase\Command\Login\Login;
use Source\Auth\Application\UseCase\Command\Login\LoginInterface;
use Source\Auth\Application\UseCase\Command\Logout\Logout;
use Source\Auth\Application\UseCase\Command\Logout\LogoutInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(LoginInterface::class, Login::class);
        $this->app->singleton(LogoutInterface::class, Logout::class);
    }
}
