<?php

declare(strict_types=1);

namespace Application\Providers\Identity;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Source\Account\Domain\Event\DelegationApproved;
use Source\Account\Domain\Event\DelegationRevoked;
use Source\Identity\Application\EventHandler\DelegationApprovedHandler;
use Source\Identity\Application\EventHandler\DelegationRevokedHandler;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);

        $events->listen(
            DelegationApproved::class,
            [DelegationApprovedHandler::class, 'handle'],
        );

        $events->listen(
            DelegationRevoked::class,
            [DelegationRevokedHandler::class, 'handle'],
        );
    }
}
