<?php

namespace Application\Providers\SiteManagement;

use Businesses\SiteManagement\Announcement\Domain\Factory\AnnouncementFactory;
use Businesses\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AnnouncementFactoryInterface::class, AnnouncementFactory::class);
    }
}
