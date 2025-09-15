<?php

namespace Application\Providers;

use Businesses\Group\Domain\Factory\GroupFactory;
use Businesses\Group\Domain\Factory\GroupFactoryInterface;
use Businesses\Member\Domain\Factory\MemberFactory;
use Businesses\Member\Domain\Factory\MemberFactoryInterface;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(MemberFactoryInterface::class, MemberFactory::class);
        $this->app->singleton(GroupFactoryInterface::class, GroupFactory::class);
    }
}