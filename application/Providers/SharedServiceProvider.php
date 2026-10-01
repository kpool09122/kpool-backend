<?php

declare(strict_types=1);

namespace Application\Providers;

use Illuminate\Support\ServiceProvider;
use Override;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\Factory\ArchivedPrincipalFactoryInterface;
use Source\Shared\Domain\Repository\ArchivedPrincipalRepositoryInterface;
use Source\Shared\Infrastructure\Factory\ArchivedPrincipalFactory;
use Source\Shared\Infrastructure\Repository\ArchivedPrincipalRepository;
use Source\Shared\Infrastructure\Service\Event\LaravelEventDispatcher;
use Source\Shared\Infrastructure\Service\ImageService;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;

class SharedServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->useLangPath(dirname(__DIR__, 2) . '/resources/lang');
    }

    public function boot(): void
    {
        $this->app->singleton(ArchivedPrincipalFactoryInterface::class, ArchivedPrincipalFactory::class);
        $this->app->singleton(ArchivedPrincipalRepositoryInterface::class, ArchivedPrincipalRepository::class);
        $this->app->singleton(UuidGeneratorInterface::class, UuidGenerator::class);
        $this->app->singleton(EventDispatcherInterface::class, LaravelEventDispatcher::class);
        $this->app->singleton(ImageServiceInterface::class, ImageService::class);
    }
}
