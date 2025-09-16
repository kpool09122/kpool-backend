<?php

namespace Application\Providers\Wiki;

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
    }
}