<?php

declare(strict_types=1);

namespace Application\Providers;

use Application\Shared\Service\Ulid\UlidGenerator;
use Businesses\Shared\Service\Ulid\UlidGeneratorInterface;
use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(UlidGeneratorInterface::class, UlidGenerator::class);
    }
}
