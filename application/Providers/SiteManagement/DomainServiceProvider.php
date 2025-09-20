<?php

declare(strict_types=1);

namespace Application\Providers\SiteManagement;

use Illuminate\Support\ServiceProvider;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactory;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Source\SiteManagement\Contact\Domain\Factory\ContactFactory;
use Source\SiteManagement\Contact\Domain\Factory\ContactFactoryInterface;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AnnouncementFactoryInterface::class, AnnouncementFactory::class);
        $this->app->singleton(ContactFactoryInterface::class, ContactFactory::class);
    }
}
