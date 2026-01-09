<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Support\ServiceProvider;
use Source\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Domain\Factory\DelegationPermissionFactoryInterface;
use Source\Account\Domain\Factory\IdentityGroupFactoryInterface;
use Source\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Domain\Repository\DelegationPermissionRepositoryInterface;
use Source\Account\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\Domain\Service\DelegationTerminationService;
use Source\Account\Domain\Service\DelegationTerminationServiceInterface;
use Source\Account\Infrastructure\Factory\AccountFactory;
use Source\Account\Infrastructure\Factory\DelegationPermissionFactory;
use Source\Account\Infrastructure\Factory\IdentityGroupFactory;
use Source\Account\Infrastructure\Repository\AccountRepository;
use Source\Account\Infrastructure\Repository\DelegationPermissionRepository;
use Source\Account\Infrastructure\Repository\IdentityGroupRepository;

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
    }
}
