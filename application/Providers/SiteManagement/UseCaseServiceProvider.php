<?php

namespace Application\Providers\SiteManagement;

use Businesses\SiteManagement\Announcement\UseCase\Command\CreateAnnouncement\CreateAnnouncement;
use Businesses\SiteManagement\Announcement\UseCase\Command\CreateAnnouncement\CreateAnnouncementInterface;
use Businesses\SiteManagement\Announcement\UseCase\Command\DeleteAnnouncement\DeleteAnnouncement;
use Businesses\SiteManagement\Announcement\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementInterface;
use Businesses\SiteManagement\Announcement\UseCase\Command\UpdateAnnouncement\UpdateAnnouncement;
use Businesses\SiteManagement\Announcement\UseCase\Command\UpdateAnnouncement\UpdateAnnouncementInterface;
use Businesses\SiteManagement\Contact\UseCase\Command\SubmitContact\SubmitContact;
use Businesses\SiteManagement\Contact\UseCase\Command\SubmitContact\SubmitContactInterface;
use Illuminate\Support\ServiceProvider;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CreateAnnouncementInterface::class, CreateAnnouncement::class);
        $this->app->singleton(UpdateAnnouncementInterface::class, UpdateAnnouncement::class);
        $this->app->singleton(DeleteAnnouncementInterface::class, DeleteAnnouncement::class);
        $this->app->singleton(SubmitContactInterface::class, SubmitContact::class);
    }
}
