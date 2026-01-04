<?php

declare(strict_types=1);

namespace Application\Providers;

use Illuminate\Support\ServiceProvider;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Infrastructure\Service\Event\LaravelEventDispatcher;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;

class SharedServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(UuidGeneratorInterface::class, UuidGenerator::class);
        $this->app->singleton(EventDispatcherInterface::class, LaravelEventDispatcher::class);
    }
}
