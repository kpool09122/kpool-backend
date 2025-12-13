<?php

declare(strict_types=1);

namespace Application\Providers\SiteManagement;

use Illuminate\Support\ServiceProvider;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactory;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Factory\DraftAnnouncementFactory;
use Source\SiteManagement\Announcement\Domain\Factory\DraftAnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Infrastructure\Adapters\Repository\AnnouncementRepository;
use Source\SiteManagement\Contact\Domain\Factory\ContactFactory;
use Source\SiteManagement\Contact\Domain\Factory\ContactFactoryInterface;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AnnouncementFactoryInterface::class, AnnouncementFactory::class);
        $this->app->singleton(AnnouncementRepositoryInterface::class, AnnouncementRepository::class);
        $this->app->singleton(ContactFactoryInterface::class, ContactFactory::class);
        $this->app->singleton(DraftAnnouncementFactoryInterface::class, DraftAnnouncementFactory::class);
    }
}
