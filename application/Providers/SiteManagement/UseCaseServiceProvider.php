<?php

declare(strict_types=1);

namespace Application\Providers\SiteManagement;

use Illuminate\Support\ServiceProvider;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement\TranslateAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement\TranslateAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncementInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContact;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInterface;
use Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser\ProvisionUser;
use Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser\ProvisionUserInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CreateAnnouncementInterface::class, CreateAnnouncement::class);
        $this->app->singleton(UpdateAnnouncementInterface::class, UpdateAnnouncement::class);
        $this->app->singleton(DeleteAnnouncementInterface::class, DeleteAnnouncement::class);
        $this->app->singleton(SubmitContactInterface::class, SubmitContact::class);
        $this->app->singleton(TranslateAnnouncementInterface::class, TranslateAnnouncement::class);
        $this->app->singleton(PublishAnnouncementInterface::class, PublishAnnouncement::class);
        $this->app->singleton(ProvisionUserInterface::class, ProvisionUser::class);
    }
}
