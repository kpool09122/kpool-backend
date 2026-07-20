<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Support\ServiceProvider;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccount;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccount;
use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountInterface;
use Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification\ApproveVerification;
use Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification\ApproveVerificationInterface;
use Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification\RejectVerification;
use Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification\RejectVerificationInterface;
use Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification\RequestVerification;
use Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification\RequestVerificationInterface;
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
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitation;
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitationInterface;
use Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CreateAccountInterface::class, CreateAccount::class);
        $this->app->singleton(CreatePrincipalGroupInterface::class, CreatePrincipalGroup::class);
        $this->app->singleton(DeletePrincipalGroupInterface::class, DeletePrincipalGroup::class);
        $this->app->singleton(AddPrincipalToPrincipalGroupInterface::class, AddPrincipalToPrincipalGroup::class);
        $this->app->singleton(RemovePrincipalFromPrincipalGroupInterface::class, RemovePrincipalFromPrincipalGroup::class);
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

        // AccountVerification
        $this->app->singleton(RequestVerificationInterface::class, RequestVerification::class);
        $this->app->singleton(ApproveVerificationInterface::class, ApproveVerification::class);
        $this->app->singleton(RejectVerificationInterface::class, RejectVerification::class);

        // Invitation
        $this->app->singleton(CreateInvitationInterface::class, CreateInvitation::class);
    }
}
