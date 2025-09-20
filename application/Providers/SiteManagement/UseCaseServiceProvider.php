<?php

declare(strict_types=1);

namespace Application\Providers\SiteManagement;

use Illuminate\Support\ServiceProvider;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncementInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContact;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInterface;

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
