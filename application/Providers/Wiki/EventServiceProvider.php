<?php

declare(strict_types=1);

namespace Application\Providers\Wiki;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Source\Account\Affiliation\Domain\Event\AffiliationActivated;
use Source\Account\Affiliation\Domain\Event\AffiliationTerminated;
use Source\Identity\Domain\Event\DelegatedIdentityCreated;
use Source\Identity\Domain\Event\DelegatedIdentityDeleted;
use Source\Wiki\Principal\Application\EventHandler\AffiliationActivatedHandler;
use Source\Wiki\Principal\Application\EventHandler\AffiliationTerminatedHandler;
use Source\Wiki\Principal\Application\EventHandler\DelegatedIdentityCreatedHandler;
use Source\Wiki\Principal\Application\EventHandler\DelegatedIdentityDeletedHandler;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);

        $events->listen(
            DelegatedIdentityCreated::class,
            [DelegatedIdentityCreatedHandler::class, 'handle'],
        );

        $events->listen(
            DelegatedIdentityDeleted::class,
            [DelegatedIdentityDeletedHandler::class, 'handle'],
        );

        $events->listen(
            AffiliationActivated::class,
            [AffiliationActivatedHandler::class, 'handle'],
        );

        $events->listen(
            AffiliationTerminated::class,
            [AffiliationTerminatedHandler::class, 'handle'],
        );
    }
}
