<?php

declare(strict_types=1);

namespace Source\Shared\Application\Service\Event;

interface EventDispatcherInterface
{
    public function dispatch(object $event): void;
}
