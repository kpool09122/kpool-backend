<?php

declare(strict_types=1);

namespace Application\Providers\Wiki;

use Application\Adapters\Wiki\Agency\Repository\AgencyRepository;
use Businesses\Wiki\Agency\Domain\Factory\AgencyFactory;
use Businesses\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Businesses\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Businesses\Wiki\Group\Domain\Factory\GroupFactory;
use Businesses\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Businesses\Wiki\Member\Domain\Factory\MemberFactory;
use Businesses\Wiki\Member\Domain\Factory\MemberFactoryInterface;
use Businesses\Wiki\Song\Domain\Factory\SongFactory;
use Businesses\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(MemberFactoryInterface::class, MemberFactory::class);
        $this->app->singleton(GroupFactoryInterface::class, GroupFactory::class);
        $this->app->singleton(SongFactoryInterface::class, SongFactory::class);
        $this->app->singleton(AgencyFactoryInterface::class, AgencyFactory::class);
        $this->app->singleton(AgencyRepositoryInterface::class, AgencyRepository::class);
    }
}
