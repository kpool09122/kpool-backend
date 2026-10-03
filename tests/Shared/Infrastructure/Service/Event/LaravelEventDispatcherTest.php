<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service\Event;

use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\TestCase;
use Source\Shared\Infrastructure\Service\Event\LaravelEventDispatcher;
use stdClass;

class LaravelEventDispatcherTest extends TestCase
{
    public function testForwardsSameEventToLaravelDispatcher(): void
    {
        $event = new stdClass();
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())->method('dispatch')->with($this->identicalTo($event));
        (new LaravelEventDispatcher($dispatcher))->dispatch($event);
    }
}
