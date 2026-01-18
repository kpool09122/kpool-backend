<?php

declare(strict_types=1);

namespace Application\Providers\Identity;

use Application\Listeners\Identity\CreateAccountOnIdentityCreated;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Source\Account\Delegation\Domain\Event\DelegationApproved;
use Source\Account\Delegation\Domain\Event\DelegationRevoked;
use Source\Identity\Application\EventHandler\DelegationApprovedHandler;
use Source\Identity\Application\EventHandler\DelegationRevokedHandler;
use Source\Identity\Domain\Event\IdentityCreated;

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

        $events->listen(
            IdentityCreated::class,
            [CreateAccountOnIdentityCreated::class, 'handle'],
        );
    }
}
