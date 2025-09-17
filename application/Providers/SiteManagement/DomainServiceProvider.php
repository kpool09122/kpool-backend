<?php

declare(strict_types=1);

namespace Application\Providers\SiteManagement;

use Businesses\SiteManagement\Announcement\Domain\Factory\AnnouncementFactory;
use Businesses\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Businesses\SiteManagement\Contact\Domain\Factory\ContactFactory;
use Businesses\SiteManagement\Contact\Domain\Factory\ContactFactoryInterface;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AnnouncementFactoryInterface::class, AnnouncementFactory::class);
        $this->app->singleton(ContactFactoryInterface::class, ContactFactory::class);
    }
}
