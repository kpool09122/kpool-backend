<?php

declare(strict_types=1);

namespace Application\Providers\Wiki;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Source\Account\Account\Domain\Event\AccountCategoryChanged;
use Source\Account\Affiliation\Domain\Event\AffiliationActivated;
use Source\Account\Affiliation\Domain\Event\AffiliationTerminated;
use Source\Wiki\Principal\Application\EventHandler\AccountCategoryChangedHandler;
use Source\Wiki\Principal\Application\EventHandler\AffiliationActivatedHandler;
use Source\Wiki\Principal\Application\EventHandler\AffiliationTerminatedHandler;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);

        $events->listen(
            AffiliationActivated::class,
            [AffiliationActivatedHandler::class, 'handle'],
        );

        $events->listen(
            AccountCategoryChanged::class,
            [AccountCategoryChangedHandler::class, 'handle'],
        );

        $events->listen(
            AffiliationTerminated::class,
            [AffiliationTerminatedHandler::class, 'handle'],
        );
    }
}
