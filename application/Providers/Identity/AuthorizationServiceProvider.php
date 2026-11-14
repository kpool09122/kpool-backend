<?php

declare(strict_types=1);

namespace Application\Providers\Identity;

use Application\Models\Identity\McpAccessToken;
use Application\Models\Identity\McpAuthCode;
use Application\Models\Identity\McpClient;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Source\Identity\Application\Service\AuthorizationConsentServiceInterface;
use Source\Identity\Application\Service\AuthorizationTokenVerifierInterface;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Infrastructure\Repository\AuthorizationConnectionRepository;
use Source\Identity\Infrastructure\Service\McpAuthorizationService;

class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Passport::ignoreRoutes();
        Passport::useTokenModel(McpAccessToken::class);
        Passport::useAuthCodeModel(McpAuthCode::class);
        Passport::useClientModel(McpClient::class);
        $this->app->singleton(AuthorizationConnectionRepositoryInterface::class, AuthorizationConnectionRepository::class);
        $this->app->singleton(AuthorizationTokenVerifierInterface::class, McpAuthorizationService::class);
        $this->app->singleton(AuthorizationConsentServiceInterface::class, McpAuthorizationService::class);
        $this->app->singleton(McpAuthorizationService::class);
    }

    public function boot(): void
    {
        Passport::tokensCan(array_combine(
            array_map(static fn (AuthorizationScope $scope): string => $scope->value, AuthorizationScope::cases()),
            array_map(static fn (AuthorizationScope $scope): string => $scope->name, AuthorizationScope::cases()),
        ));
    }
}
