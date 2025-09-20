<?php

declare(strict_types=1);

namespace Application\Providers;

use Illuminate\Support\ServiceProvider;
use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Infrastructure\Service\Ulid\UlidGenerator;

class SharedServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(UlidGeneratorInterface::class, UlidGenerator::class);
    }
}
