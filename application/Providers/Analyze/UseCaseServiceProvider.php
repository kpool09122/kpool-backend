<?php

declare(strict_types=1);

namespace Application\Providers\Analyze;

use Illuminate\Support\ServiceProvider;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipal;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromService;
use Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\Analyze\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Analyze\Principal\Infrastructure\Query\GetCurrentPrincipal;

class UseCaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GetCurrentPrincipalInterface::class, GetCurrentPrincipal::class);
        $this->app->singleton(CreatePrincipalInterface::class, CreatePrincipal::class);
        $this->app->singleton(WithdrawFromServiceInterface::class, WithdrawFromService::class);
    }
}
