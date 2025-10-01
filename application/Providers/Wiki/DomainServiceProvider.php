<?php

declare(strict_types=1);

namespace Application\Providers\Wiki;

use Illuminate\Support\ServiceProvider;
use Source\Wiki\Agency\Domain\Factory\AgencyFactory;
use Source\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Factory\DraftAgencyFactory;
use Source\Wiki\Agency\Domain\Factory\DraftAgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Infrastracture\Adapters\Repository\AgencyRepository;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactory;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactoryInterface;
use Source\Wiki\Group\Domain\Factory\GroupFactory;
use Source\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Source\Wiki\Group\Domain\Service\GroupService;
use Source\Wiki\Group\Domain\Service\GroupServiceInterface;
use Source\Wiki\Member\Domain\Factory\DraftMemberFactory;
use Source\Wiki\Member\Domain\Factory\DraftMemberFactoryInterface;
use Source\Wiki\Member\Domain\Factory\MemberFactory;
use Source\Wiki\Member\Domain\Factory\MemberFactoryInterface;
use Source\Wiki\Member\Domain\Service\MemberService;
use Source\Wiki\Member\Domain\Service\MemberServiceInterface;
use Source\Wiki\Song\Domain\Factory\DraftSongFactory;
use Source\Wiki\Song\Domain\Factory\DraftSongFactoryInterface;
use Source\Wiki\Song\Domain\Factory\SongFactory;
use Source\Wiki\Song\Domain\Factory\SongFactoryInterface;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(MemberFactoryInterface::class, MemberFactory::class);
        $this->app->singleton(MemberServiceInterface::class, MemberService::class);
        $this->app->singleton(GroupFactoryInterface::class, GroupFactory::class);
        $this->app->singleton(GroupServiceInterface::class, GroupService::class);
        $this->app->singleton(SongFactoryInterface::class, SongFactory::class);
        $this->app->singleton(AgencyFactoryInterface::class, AgencyFactory::class);
        $this->app->singleton(DraftAgencyFactoryInterface::class, DraftAgencyFactory::class);
        $this->app->singleton(AgencyRepositoryInterface::class, AgencyRepository::class);
        $this->app->singleton(DraftGroupFactoryInterface::class, DraftGroupFactory::class);
        $this->app->singleton(DraftMemberFactoryInterface::class, DraftMemberFactory::class);
        $this->app->singleton(DraftSongFactoryInterface::class, DraftSongFactory::class);
    }
}
