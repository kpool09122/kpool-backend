<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Source\Account\Account\Application\EventHandler\IdentityCreatedHandler;
use Source\Identity\Domain\Event\IdentityCreated;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);

        $events->listen(
            IdentityCreated::class,
            [IdentityCreatedHandler::class, 'handle'],
        );
    }
}
