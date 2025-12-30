<?php

declare(strict_types=1);

namespace Application\Providers\SiteManagement;

use Illuminate\Support\ServiceProvider;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactory;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Factory\DraftAnnouncementFactory;
use Source\SiteManagement\Announcement\Domain\Factory\DraftAnnouncementFactoryInterface;
use Source\SiteManagement\Contact\Domain\Factory\ContactFactory;
use Source\SiteManagement\Contact\Domain\Factory\ContactFactoryInterface;
use Source\SiteManagement\User\Domain\Factory\UserFactoryInterface;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;
use Source\SiteManagement\User\Infrastructure\Factory\UserFactory;
use Source\SiteManagement\User\Infrastructure\Repository\UserRepository;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AnnouncementFactoryInterface::class, AnnouncementFactory::class);
        $this->app->singleton(ContactFactoryInterface::class, ContactFactory::class);
        $this->app->singleton(DraftAnnouncementFactoryInterface::class, DraftAnnouncementFactory::class);
        $this->app->singleton(UserFactoryInterface::class, UserFactory::class);
        $this->app->singleton(UserRepositoryInterface::class, UserRepository::class);
    }
}
