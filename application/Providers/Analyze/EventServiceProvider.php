<?php

declare(strict_types=1);

namespace Application\Providers\Analyze;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Source\Analyze\Principal\Application\EventHandler\IdentityWithdrawingHandler;
use Source\Identity\Domain\Event\IdentityWithdrawing;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);
        $events->listen(IdentityWithdrawing::class, [IdentityWithdrawingHandler::class, 'handle']);
    }
}
