<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Support\ServiceProvider;
use Source\Account\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Infrastructure\Factory\AccountFactory;
use Source\Account\Account\Infrastructure\Repository\AccountRepository;
use Source\Account\AccountVerification\Application\Service\DocumentStorageServiceInterface;
use Source\Account\AccountVerification\Domain\Factory\AccountVerificationFactoryInterface;
use Source\Account\AccountVerification\Domain\Repository\AccountVerificationRepositoryInterface;
use Source\Account\AccountVerification\Domain\Service\DocumentRequirementValidator;
use Source\Account\AccountVerification\Domain\Service\DocumentRequirementValidatorInterface;
use Source\Account\AccountVerification\Infrastructure\Factory\AccountVerificationFactory;
use Source\Account\AccountVerification\Infrastructure\Repository\AccountVerificationRepository;
use Source\Account\AccountVerification\Infrastructure\Service\DocumentStorageService;
use Source\Account\Delegation\Domain\Service\DelegationTerminationService;
use Source\Account\Delegation\Domain\Service\DelegationTerminationServiceInterface;
use Source\Account\DelegationPermission\Domain\Factory\DelegationPermissionFactoryInterface;
use Source\Account\DelegationPermission\Domain\Repository\DelegationPermissionRepositoryInterface;
use Source\Account\DelegationPermission\Infrastructure\Factory\DelegationPermissionFactory;
use Source\Account\DelegationPermission\Infrastructure\Repository\DelegationPermissionRepository;
use Source\Account\IdentityGroup\Domain\Factory\IdentityGroupFactoryInterface;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\IdentityGroup\Infrastructure\Factory\IdentityGroupFactory;
use Source\Account\IdentityGroup\Infrastructure\Repository\IdentityGroupRepository;
use Source\Account\Invitation\Domain\Factory\InvitationFactoryInterface;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Invitation\Domain\Service\InvitationMailServiceInterface;
use Source\Account\Invitation\Infrastructure\Factory\InvitationFactory;
use Source\Account\Invitation\Infrastructure\Repository\InvitationRepository;
use Source\Account\Invitation\Infrastructure\Service\InvitationMailService;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AccountFactoryInterface::class, AccountFactory::class);
        $this->app->singleton(AccountRepositoryInterface::class, AccountRepository::class);
        $this->app->singleton(IdentityGroupFactoryInterface::class, IdentityGroupFactory::class);
        $this->app->singleton(IdentityGroupRepositoryInterface::class, IdentityGroupRepository::class);
        $this->app->singleton(DelegationPermissionFactoryInterface::class, DelegationPermissionFactory::class);
        $this->app->singleton(DelegationPermissionRepositoryInterface::class, DelegationPermissionRepository::class);
        $this->app->singleton(DelegationTerminationServiceInterface::class, DelegationTerminationService::class);

        // AccountVerification
        $this->app->singleton(AccountVerificationFactoryInterface::class, AccountVerificationFactory::class);
        $this->app->singleton(AccountVerificationRepositoryInterface::class, AccountVerificationRepository::class);
        $this->app->singleton(DocumentStorageServiceInterface::class, DocumentStorageService::class);
        $this->app->singleton(DocumentRequirementValidator::class, DocumentRequirementValidator::class);
        $this->app->singleton(DocumentRequirementValidatorInterface::class, DocumentRequirementValidator::class);

        // Invitation
        $this->app->singleton(InvitationFactoryInterface::class, InvitationFactory::class);
        $this->app->singleton(InvitationRepositoryInterface::class, InvitationRepository::class);
        $this->app->singleton(InvitationMailServiceInterface::class, fn ($app) => new InvitationMailService(
            $app->make(AccountRepositoryInterface::class),
            $app->make(IdentityRepositoryInterface::class),
            config('app.frontend_url', 'http://localhost:3000'),
        ));
    }
}
