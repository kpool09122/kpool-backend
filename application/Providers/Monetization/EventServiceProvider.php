<?php

declare(strict_types=1);

namespace Application\Providers\Monetization;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Source\Account\Account\Domain\Event\AccountDeleting;
use Source\Monetization\Account\Application\EventHandler\AccountDeletingHandler;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(Dispatcher::class)->listen(AccountDeleting::class, [AccountDeletingHandler::class, 'handle']);
    }
}
