<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Support\ServiceProvider;
use Source\Account\Account\Application\Service\AccountDocumentFileTypeDetectorInterface;
use Source\Account\Account\Application\Service\DocumentStorageServiceInterface;
use Source\Account\Account\Domain\Factory\AccountCategoryChangeRequestFactoryInterface;
use Source\Account\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Service\AccountDocumentRequirementValidator;
use Source\Account\Account\Domain\Service\AccountDocumentRequirementValidatorInterface;
use Source\Account\Account\Infrastructure\Factory\AccountCategoryChangeRequestFactory;
use Source\Account\Account\Infrastructure\Factory\AccountFactory;
use Source\Account\Account\Infrastructure\Repository\AccountCategoryChangeRequestRepository;
use Source\Account\Account\Infrastructure\Repository\AccountRepository;
use Source\Account\Account\Infrastructure\Service\AccountDocumentFileTypeDetector;
use Source\Account\Account\Infrastructure\Service\DocumentStorageService;
use Source\Account\Delegation\Domain\Service\DelegationTerminationService;
use Source\Account\Delegation\Domain\Service\DelegationTerminationServiceInterface;
use Source\Account\DelegationPermission\Domain\Factory\DelegationPermissionFactoryInterface;
use Source\Account\DelegationPermission\Domain\Repository\DelegationPermissionRepositoryInterface;
use Source\Account\DelegationPermission\Infrastructure\Factory\DelegationPermissionFactory;
use Source\Account\DelegationPermission\Infrastructure\Repository\DelegationPermissionRepository;
use Source\Account\Invitation\Domain\Factory\InvitationFactoryInterface;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Invitation\Domain\Service\InvitationMailServiceInterface;
use Source\Account\Invitation\Infrastructure\Factory\InvitationFactory;
use Source\Account\Invitation\Infrastructure\Repository\InvitationRepository;
use Source\Account\Invitation\Infrastructure\Service\InvitationMailService;
use Source\Account\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface as PolicyEvaluatorInterface;
use Source\Account\Principal\Infrastructure\Factory\PrincipalFactory;
use Source\Account\Principal\Infrastructure\Factory\PrincipalGroupFactory;
use Source\Account\Principal\Infrastructure\Repository\PolicyRepository;
use Source\Account\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Source\Account\Principal\Infrastructure\Repository\PrincipalRepository;
use Source\Account\Principal\Infrastructure\Repository\RoleRepository;
use Source\Account\Principal\Infrastructure\Service\PolicyEvaluator as PolicyEvaluator;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AccountFactoryInterface::class, AccountFactory::class);
        $this->app->singleton(AccountRepositoryInterface::class, AccountRepository::class);
        $this->app->singleton(PrincipalFactoryInterface::class, PrincipalFactory::class);
        $this->app->singleton(PrincipalRepositoryInterface::class, PrincipalRepository::class);
        $this->app->singleton(PrincipalGroupFactoryInterface::class, PrincipalGroupFactory::class);
        $this->app->singleton(PrincipalGroupRepositoryInterface::class, PrincipalGroupRepository::class);
        $this->app->singleton(PolicyRepositoryInterface::class, PolicyRepository::class);
        $this->app->singleton(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->singleton(PolicyEvaluatorInterface::class, PolicyEvaluator::class);
        $this->app->singleton(DelegationPermissionFactoryInterface::class, DelegationPermissionFactory::class);
        $this->app->singleton(DelegationPermissionRepositoryInterface::class, DelegationPermissionRepository::class);
        $this->app->singleton(DelegationTerminationServiceInterface::class, DelegationTerminationService::class);

        $this->app->singleton(AccountCategoryChangeRequestFactoryInterface::class, AccountCategoryChangeRequestFactory::class);
        $this->app->singleton(AccountCategoryChangeRequestRepositoryInterface::class, AccountCategoryChangeRequestRepository::class);
        $this->app->singleton(DocumentStorageServiceInterface::class, DocumentStorageService::class);
        $this->app->singleton(AccountDocumentFileTypeDetectorInterface::class, AccountDocumentFileTypeDetector::class);
        $this->app->singleton(AccountDocumentRequirementValidatorInterface::class, AccountDocumentRequirementValidator::class);

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
