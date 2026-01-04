<?php

declare(strict_types=1);

namespace Source\Shared\Infrastructure\Service\Event;

use Illuminate\Contracts\Events\Dispatcher;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class LaravelEventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
    ) {
    }

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
