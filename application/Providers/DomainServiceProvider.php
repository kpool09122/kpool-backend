<?php

namespace Application\Providers;

use Businesses\Member\Domain\Factory\MemberFactory;
use Businesses\Member\Domain\Factory\MemberFactoryInterface;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(MemberFactoryInterface::class, MemberFactory::class);
    }
}