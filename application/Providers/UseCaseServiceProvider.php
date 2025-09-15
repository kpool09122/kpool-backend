<?php

namespace Application\Providers;

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
    }
}