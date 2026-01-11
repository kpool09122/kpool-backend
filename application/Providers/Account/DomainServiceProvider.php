<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Support\ServiceProvider;
use Source\Account\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Infrastructure\Factory\AccountFactory;
use Source\Account\Account\Infrastructure\Repository\AccountRepository;
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
