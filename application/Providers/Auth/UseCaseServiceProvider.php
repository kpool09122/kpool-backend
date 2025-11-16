<?php

declare(strict_types=1);

namespace Application\Providers\Auth;

use Illuminate\Support\ServiceProvider;
use Source\Auth\Application\UseCase\Command\Login\Login;
use Source\Auth\Application\UseCase\Command\Login\LoginInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(LoginInterface::class, Login::class);
    }
}
