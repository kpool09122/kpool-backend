<?php

declare(strict_types=1);

namespace Application\Providers\Identity;

use Application\Http\Client\OAuthHttpClient;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Service\DelegationValidatorInterface;
use Source\Identity\Domain\Factory\AuthCodeSessionFactoryInterface;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthCodeServiceInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Infrastructure\Factory\AuthCodeSessionFactory;
use Source\Identity\Infrastructure\Factory\IdentityFactory;
use Source\Identity\Infrastructure\Repository\AuthCodeSessionRepository;
use Source\Identity\Infrastructure\Repository\IdentityRepository;
use Source\Identity\Infrastructure\Service\AuthCodeService;
use Source\Identity\Infrastructure\Service\AuthService;
use Source\Identity\Infrastructure\Service\DelegationValidator;
use Source\Identity\Infrastructure\Service\SocialOAuthService;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AuthCodeSessionFactoryInterface::class, AuthCodeSessionFactory::class);
        $this->app->singleton(AuthCodeSessionRepositoryInterface::class, AuthCodeSessionRepository::class);
        $this->app->singleton(IdentityFactoryInterface::class, IdentityFactory::class);
        $this->app->singleton(IdentityRepositoryInterface::class, IdentityRepository::class);
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
        $this->app->singleton(AuthCodeServiceInterface::class, AuthCodeService::class);
        $this->app->singleton(DelegationValidatorInterface::class, DelegationValidator::class);

        $this->app->singleton(SocialOAuthServiceInterface::class, function ($app) {
            /** @var \Illuminate\Contracts\Foundation\Application $app */
            /** @var array<string, array<string, mixed>> $oauthConfig */
            $oauthConfig = config('oauth', []);

            return new SocialOAuthService(
                $app->make(OAuthHttpClient::class),
                $oauthConfig,
                $app->make(LoggerInterface::class),
            );
        });
    }
}
