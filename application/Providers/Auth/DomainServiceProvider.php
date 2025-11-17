<?php

declare(strict_types=1);

namespace Application\Providers\Auth;

use Illuminate\Support\ServiceProvider;
use Source\Auth\Domain\Factory\AuthCodeSessionFactory;
use Source\Auth\Domain\Factory\AuthCodeSessionFactoryInterface;
use Source\Auth\Domain\Factory\UserFactoryInterface;
use Source\Auth\Infrastructure\Factory\UserFactory;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AuthCodeSessionFactoryInterface::class, AuthCodeSessionFactory::class);
        $this->app->singleton(UserFactoryInterface::class, UserFactory::class);
    }
}
