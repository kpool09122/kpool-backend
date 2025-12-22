<?php

declare(strict_types=1);

namespace Application\Providers\Auth;

use Illuminate\Support\ServiceProvider;
use Source\Auth\Domain\Factory\AuthCodeSessionFactory;
use Source\Auth\Domain\Factory\AuthCodeSessionFactoryInterface;
use Source\Auth\Domain\Factory\UserFactoryInterface;
use Source\Auth\Domain\Service\AuthCodeServiceInterface;
use Source\Auth\Domain\Service\AuthServiceInterface;
use Source\Auth\Infrastructure\Factory\UserFactory;
use Source\Auth\Infrastructure\Service\AuthCodeService;
use Source\Auth\Infrastructure\Service\AuthService;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AuthCodeSessionFactoryInterface::class, AuthCodeSessionFactory::class);
        $this->app->singleton(UserFactoryInterface::class, UserFactory::class);
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
        $this->app->singleton(AuthCodeServiceInterface::class, AuthCodeService::class);
    }
}
