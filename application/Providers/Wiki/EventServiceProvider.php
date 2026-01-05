<?php

declare(strict_types=1);

namespace Application\Providers\Wiki;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Source\Identity\Domain\Event\DelegatedIdentityCreated;
use Source\Identity\Domain\Event\DelegatedIdentityDeleted;
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
    }
}
