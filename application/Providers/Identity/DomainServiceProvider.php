<?php

declare(strict_types=1);

namespace Application\Providers\Identity;

use Application\Http\Client\OAuthHttpClient\OAuthHttpClient;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Service\AffiliationRequestNotificationServiceInterface;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\CollaboratorNotificationServiceInterface;
use Source\Identity\Application\Service\SignupInvitationValidatorInterface;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Factory\AuthCodeSessionFactoryInterface;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Factory\PasskeyCredentialFactoryInterface;
use Source\Identity\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\SignupSessionRepositoryInterface;
use Source\Identity\Domain\Service\AuthCodeServiceInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\Service\OAuthStateGenerator;
use Source\Identity\Domain\Service\OAuthStateGeneratorInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\Service\WebAuthnChallengeGeneratorInterface;
use Source\Identity\Infrastructure\Factory\AuthCodeSessionFactory;
use Source\Identity\Infrastructure\Factory\IdentityFactory;
use Source\Identity\Infrastructure\Factory\PasskeyCredentialFactory;
use Source\Identity\Infrastructure\Repository\AuthCodeSessionRepository;
use Source\Identity\Infrastructure\Repository\IdentityRepository;
use Source\Identity\Infrastructure\Repository\OAuthStateRepository;
use Source\Identity\Infrastructure\Repository\PasskeyCredentialRepository;
use Source\Identity\Infrastructure\Repository\SignupSessionRepository;
use Source\Identity\Infrastructure\Service\AffiliationRequestNotificationService;
use Source\Identity\Infrastructure\Service\AuthCodeService;
use Source\Identity\Infrastructure\Service\AuthService;
use Source\Identity\Infrastructure\Service\ChallengeSessionStorageService;
use Source\Identity\Infrastructure\Service\CollaboratorNotificationService;
use Source\Identity\Infrastructure\Service\SignupInvitationValidator;
use Source\Identity\Infrastructure\Service\SocialOAuthService;
use Source\Identity\Infrastructure\Service\WebAuthnChallengeGenerator;
use Source\Identity\Infrastructure\Service\WebAuthnService;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AuthCodeSessionFactoryInterface::class, AuthCodeSessionFactory::class);
        $this->app->singleton(AuthCodeSessionRepositoryInterface::class, AuthCodeSessionRepository::class);
        $this->app->singleton(IdentityFactoryInterface::class, IdentityFactory::class);
        $this->app->singleton(PasskeyCredentialFactoryInterface::class, PasskeyCredentialFactory::class);
        $this->app->singleton(IdentityRepositoryInterface::class, IdentityRepository::class);
        $this->app->singleton(PasskeyCredentialRepositoryInterface::class, PasskeyCredentialRepository::class);
        $this->app->singleton(ChallengeSessionStorageServiceInterface::class, ChallengeSessionStorageService::class);
        $this->app->singleton(SignupInvitationValidatorInterface::class, SignupInvitationValidator::class);
        $this->app->singleton(WebAuthnChallengeGeneratorInterface::class, WebAuthnChallengeGenerator::class);
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
        $this->app->singleton(AuthCodeServiceInterface::class, AuthCodeService::class);
        $this->app->singleton(AffiliationRequestNotificationServiceInterface::class, fn ($app) => new AffiliationRequestNotificationService(
            $app->make(IdentityRepositoryInterface::class),
            config('app.frontend_url', 'http://localhost:3000'),
        ));
        $this->app->singleton(CollaboratorNotificationServiceInterface::class, CollaboratorNotificationService::class);
        $this->app->singleton(OAuthStateGeneratorInterface::class, OAuthStateGenerator::class);
        $this->app->singleton(OAuthStateRepositoryInterface::class, OAuthStateRepository::class);
        $this->app->singleton(SignupSessionRepositoryInterface::class, SignupSessionRepository::class);
        $this->app->singleton(WebAuthnServiceInterface::class, static fn (): WebAuthnService => new WebAuthnService(
            (string) config('webauthn.rp_id'),
            (string) config('webauthn.rp_name'),
            array_values(config('webauthn.allowed_origins', [])),
            (int) config('webauthn.timeout_ms', 300000),
        ));

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
