<?php

declare(strict_types=1);

namespace Application\Providers\Wiki;

use Application\Adapters\Wiki\Agency\Query\GetAgencies;
use Application\Adapters\Wiki\Agency\Query\GetAgency;
use Businesses\Wiki\Agency\UseCase\Command\CreateAgency\CreateAgency;
use Businesses\Wiki\Agency\UseCase\Command\CreateAgency\CreateAgencyInterface;
use Businesses\Wiki\Agency\UseCase\Command\EditAgency\EditAgency;
use Businesses\Wiki\Agency\UseCase\Command\EditAgency\EditAgencyInterface;
use Businesses\Wiki\Agency\UseCase\Query\GetAgencies\GetAgenciesInterface;
use Businesses\Wiki\Agency\UseCase\Query\GetAgency\GetAgencyInterface;
use Businesses\Wiki\Group\UseCase\Command\CreateGroup\CreateGroup;
use Businesses\Wiki\Group\UseCase\Command\CreateGroup\CreateGroupInterface;
use Businesses\Wiki\Group\UseCase\Command\EditGroup\EditGroup;
use Businesses\Wiki\Group\UseCase\Command\EditGroup\EditGroupInterface;
use Businesses\Wiki\Member\UseCase\Command\CreateMember\CreateMember;
use Businesses\Wiki\Member\UseCase\Command\CreateMember\CreateMemberInterface;
use Businesses\Wiki\Member\UseCase\Command\EditMember\EditMember;
use Businesses\Wiki\Member\UseCase\Command\EditMember\EditMemberInterface;
use Businesses\Wiki\Song\UseCase\Command\CreateSong\CreateSong;
use Businesses\Wiki\Song\UseCase\Command\CreateSong\CreateSongInterface;
use Businesses\Wiki\Song\UseCase\Command\EditSong\EditSong;
use Businesses\Wiki\Song\UseCase\Command\EditSong\EditSongInterface;
use Illuminate\Support\ServiceProvider;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CreateMemberInterface::class, CreateMember::class);
        $this->app->singleton(EditMemberInterface::class, EditMember::class);
        $this->app->singleton(CreateGroupInterface::class, CreateGroup::class);
        $this->app->singleton(EditGroupInterface::class, EditGroup::class);
        $this->app->singleton(CreateSongInterface::class, CreateSong::class);
        $this->app->singleton(EditSongInterface::class, EditSong::class);
        $this->app->singleton(CreateAgencyInterface::class, CreateAgency::class);
        $this->app->singleton(EditAgencyInterface::class, EditAgency::class);
        $this->app->singleton(GetAgencyInterface::class, GetAgency::class);
        $this->app->singleton(GetAgenciesInterface::class, GetAgencies::class);
    }
}
