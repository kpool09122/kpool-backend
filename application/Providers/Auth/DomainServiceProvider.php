<?php

declare(strict_types=1);

namespace Application\Providers\Auth;

use Illuminate\Support\ServiceProvider;
use Source\Auth\Domain\Factory\AuthCodeSessionFactory;
use Source\Auth\Domain\Factory\AuthCodeSessionFactoryInterface;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AuthCodeSessionFactoryInterface::class, AuthCodeSessionFactory::class);
        $this->app->singleton(AuthCodeSessionFactoryInterface::class, AuthCodeSessionFactory::class);
    }
}
