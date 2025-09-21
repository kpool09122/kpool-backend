<?php

declare(strict_types=1);

namespace Application\Providers\Wiki;

use Illuminate\Support\ServiceProvider;
use Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl\ChangeAccessControl;
use Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl\ChangeAccessControlInterface;
use Source\Wiki\Agency\Application\UseCase\Command\CreateAgency\CreateAgency;
use Source\Wiki\Agency\Application\UseCase\Command\CreateAgency\CreateAgencyInterface;
use Source\Wiki\Agency\Application\UseCase\Command\EditAgency\EditAgency;
use Source\Wiki\Agency\Application\UseCase\Command\EditAgency\EditAgencyInterface;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgencies\GetAgenciesInterface;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgency\GetAgencyInterface;
use Source\Wiki\Agency\Infrastracture\Adapters\GetAgencies;
use Source\Wiki\Agency\Infrastracture\Adapters\GetAgency;
use Source\Wiki\Group\Application\UseCase\Command\CreateGroup\CreateGroup;
use Source\Wiki\Group\Application\UseCase\Command\CreateGroup\CreateGroupInterface;
use Source\Wiki\Group\Application\UseCase\Command\EditGroup\EditGroup;
use Source\Wiki\Group\Application\UseCase\Command\EditGroup\EditGroupInterface;
use Source\Wiki\Member\Application\UseCase\Command\CreateMember\CreateMember;
use Source\Wiki\Member\Application\UseCase\Command\CreateMember\CreateMemberInterface;
use Source\Wiki\Member\Application\UseCase\Command\EditMember\EditMember;
use Source\Wiki\Member\Application\UseCase\Command\EditMember\EditMemberInterface;
use Source\Wiki\Song\Application\UseCase\Command\CreateSong\CreateSong;
use Source\Wiki\Song\Application\UseCase\Command\CreateSong\CreateSongInterface;
use Source\Wiki\Song\Application\UseCase\Command\EditSong\EditSong;
use Source\Wiki\Song\Application\UseCase\Command\EditSong\EditSongInterface;

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
        $this->app->singleton(ChangeAccessControlInterface::class, ChangeAccessControl::class);
    }
}
