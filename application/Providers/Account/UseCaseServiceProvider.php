<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Support\ServiceProvider;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccount;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccount;
use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountInterface;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliation;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationInterface;
use Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation\RejectAffiliation;
use Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation\RejectAffiliationInterface;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliation;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInterface;
use Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliation;
use Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliationInterface;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegation;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationInterface;
use Source\Account\Delegation\Application\UseCase\Command\RequestDelegation\RequestDelegation;
use Source\Account\Delegation\Application\UseCase\Command\RequestDelegation\RequestDelegationInterface;
use Source\Account\Delegation\Application\UseCase\Command\RevokeDelegation\RevokeDelegation;
use Source\Account\Delegation\Application\UseCase\Command\RevokeDelegation\RevokeDelegationInterface;
use Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermission;
use Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermissionInterface;
use Source\Account\DelegationPermission\Application\UseCase\Command\RevokeDelegationPermission\RevokeDelegationPermission as RevokeDelegationPermissionUseCase;
use Source\Account\DelegationPermission\Application\UseCase\Command\RevokeDelegationPermission\RevokeDelegationPermissionInterface as RevokeDelegationPermissionInterfaceNew;
use Source\Account\IdentityGroup\Application\UseCase\Command\AddIdentityToIdentityGroup\AddIdentityToIdentityGroup;
use Source\Account\IdentityGroup\Application\UseCase\Command\AddIdentityToIdentityGroup\AddIdentityToIdentityGroupInterface;
use Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup\CreateIdentityGroup;
use Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup\CreateIdentityGroupInterface;
use Source\Account\IdentityGroup\Application\UseCase\Command\DeleteIdentityGroup\DeleteIdentityGroup;
use Source\Account\IdentityGroup\Application\UseCase\Command\DeleteIdentityGroup\DeleteIdentityGroupInterface;
use Source\Account\IdentityGroup\Application\UseCase\Command\RemoveIdentityFromIdentityGroup\RemoveIdentityFromIdentityGroup;
use Source\Account\IdentityGroup\Application\UseCase\Command\RemoveIdentityFromIdentityGroup\RemoveIdentityFromIdentityGroupInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CreateAccountInterface::class, CreateAccount::class);
        $this->app->singleton(CreateIdentityGroupInterface::class, CreateIdentityGroup::class);
        $this->app->singleton(DeleteIdentityGroupInterface::class, DeleteIdentityGroup::class);
        $this->app->singleton(AddIdentityToIdentityGroupInterface::class, AddIdentityToIdentityGroup::class);
        $this->app->singleton(RemoveIdentityFromIdentityGroupInterface::class, RemoveIdentityFromIdentityGroup::class);
        $this->app->singleton(GrantDelegationPermissionInterface::class, GrantDelegationPermission::class);
        $this->app->singleton(RevokeDelegationPermissionInterfaceNew::class, RevokeDelegationPermissionUseCase::class);
        $this->app->singleton(DeleteAccountInterface::class, DeleteAccount::class);
        $this->app->singleton(RevokeDelegationInterface::class, RevokeDelegation::class);
        $this->app->singleton(RequestDelegationInterface::class, RequestDelegation::class);
        $this->app->singleton(ApproveDelegationInterface::class, ApproveDelegation::class);
        $this->app->singleton(ApproveAffiliationInterface::class, ApproveAffiliation::class);
        $this->app->singleton(TerminateAffiliationInterface::class, TerminateAffiliation::class);
        $this->app->singleton(RequestAffiliationInterface::class, RequestAffiliation::class);
        $this->app->singleton(RejectAffiliationInterface::class, RejectAffiliation::class);
    }
}
