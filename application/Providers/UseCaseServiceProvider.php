<?php

namespace Application\Providers;

use Businesses\Group\UseCase\Command\CreateGroup\CreateGroup;
use Businesses\Group\UseCase\Command\CreateGroup\CreateGroupInterface;
use Businesses\Group\UseCase\Command\EditGroup\EditGroup;
use Businesses\Group\UseCase\Command\EditGroup\EditGroupInterface;
use Businesses\Member\UseCase\Command\CreateMember\CreateMember;
use Businesses\Member\UseCase\Command\CreateMember\CreateMemberInterface;
use Businesses\Member\UseCase\Command\EditMember\EditMember;
use Businesses\Member\UseCase\Command\EditMember\EditMemberInterface;
use Illuminate\Support\ServiceProvider;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CreateMemberInterface::class, CreateMember::class);
        $this->app->singleton(EditMemberInterface::class, EditMember::class);
        $this->app->singleton(CreateGroupInterface::class, CreateGroup::class);
        $this->app->singleton(EditGroupInterface::class, EditGroup::class);
    }
}