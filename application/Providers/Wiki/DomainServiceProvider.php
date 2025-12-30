<?php

declare(strict_types=1);

namespace Application\Providers\Wiki;

use Illuminate\Support\ServiceProvider;
use Source\Wiki\Agency\Domain\Factory\AgencyFactory;
use Source\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactory;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactoryInterface;
use Source\Wiki\Agency\Domain\Factory\AgencySnapshotFactory;
use Source\Wiki\Agency\Domain\Factory\AgencySnapshotFactoryInterface;
use Source\Wiki\Agency\Domain\Factory\DraftAgencyFactory;
use Source\Wiki\Agency\Domain\Factory\DraftAgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencySnapshotRepositoryInterface;
use Source\Wiki\Agency\Domain\Service\AgencyService;
use Source\Wiki\Agency\Domain\Service\AgencyServiceInterface;
use Source\Wiki\Agency\Infrastructure\Adapters\Repository\AgencyRepository;
use Source\Wiki\Agency\Infrastructure\Adapters\Repository\AgencySnapshotRepository;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactory;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactoryInterface;
use Source\Wiki\Group\Domain\Factory\GroupFactory;
use Source\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactory;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\Service\GroupService;
use Source\Wiki\Group\Domain\Service\GroupServiceInterface;
use Source\Wiki\Group\Infrastructure\Adapters\Repository\GroupRepository;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Infrastructure\Factory\PrincipalFactory;
use Source\Wiki\Principal\Infrastructure\Repository\PrincipalRepository;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Infrastructure\Service\NormalizationService;
use Source\Wiki\Song\Domain\Factory\DraftSongFactory;
use Source\Wiki\Song\Domain\Factory\DraftSongFactoryInterface;
use Source\Wiki\Song\Domain\Factory\SongFactory;
use Source\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactory;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\Service\SongService;
use Source\Wiki\Song\Domain\Service\SongServiceInterface;
use Source\Wiki\Talent\Domain\Factory\DraftTalentFactory;
use Source\Wiki\Talent\Domain\Factory\DraftTalentFactoryInterface;
use Source\Wiki\Talent\Domain\Factory\TalentFactory;
use Source\Wiki\Talent\Domain\Factory\TalentFactoryInterface;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactory;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactoryInterface;
use Source\Wiki\Talent\Domain\Service\TalentService;
use Source\Wiki\Talent\Domain\Service\TalentServiceInterface;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(TalentFactoryInterface::class, TalentFactory::class);
        $this->app->singleton(TalentHistoryFactoryInterface::class, TalentHistoryFactory::class);
        $this->app->singleton(TalentServiceInterface::class, TalentService::class);
        $this->app->singleton(GroupFactoryInterface::class, GroupFactory::class);
        $this->app->singleton(GroupHistoryFactoryInterface::class, GroupHistoryFactory::class);
        $this->app->singleton(GroupServiceInterface::class, GroupService::class);
        $this->app->singleton(GroupRepositoryInterface::class, GroupRepository::class);
        $this->app->singleton(SongFactoryInterface::class, SongFactory::class);
        $this->app->singleton(SongHistoryFactoryInterface::class, SongHistoryFactory::class);
        $this->app->singleton(SongServiceInterface::class, SongService::class);
        $this->app->singleton(AgencyFactoryInterface::class, AgencyFactory::class);
        $this->app->singleton(AgencyHistoryFactoryInterface::class, AgencyHistoryFactory::class);
        $this->app->singleton(AgencySnapshotFactoryInterface::class, AgencySnapshotFactory::class);
        $this->app->singleton(AgencyServiceInterface::class, AgencyService::class);
        $this->app->singleton(DraftAgencyFactoryInterface::class, DraftAgencyFactory::class);
        $this->app->singleton(AgencyRepositoryInterface::class, AgencyRepository::class);
        $this->app->singleton(AgencySnapshotRepositoryInterface::class, AgencySnapshotRepository::class);
        $this->app->singleton(DraftGroupFactoryInterface::class, DraftGroupFactory::class);
        $this->app->singleton(DraftTalentFactoryInterface::class, DraftTalentFactory::class);
        $this->app->singleton(DraftSongFactoryInterface::class, DraftSongFactory::class);
        $this->app->singleton(NormalizationServiceInterface::class, NormalizationService::class);
        $this->app->singleton(PrincipalFactoryInterface::class, PrincipalFactory::class);
        $this->app->singleton(PrincipalRepositoryInterface::class, PrincipalRepository::class);
    }
}
