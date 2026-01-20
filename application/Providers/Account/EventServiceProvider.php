<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Source\Account\Account\Application\EventHandler\IdentityCreatedHandler;
use Source\Account\Invitation\Application\EventHandler\IdentityCreatedViaInvitationHandler;
use Source\Account\Invitation\Application\EventHandler\InvitationCreatedHandler;
use Source\Account\Invitation\Domain\Event\InvitationCreated;
use Source\Identity\Domain\Event\IdentityCreated;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;

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

        $events->listen(
            InvitationCreated::class,
            [InvitationCreatedHandler::class, 'handle'],
        );

        $events->listen(
            IdentityCreatedViaInvitation::class,
            [IdentityCreatedViaInvitationHandler::class, 'handle'],
        );
    }
}
