<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Support\ServiceProvider;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequest;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Command\ApproveVerification\ApproveVerification;
use Source\Account\Account\Application\UseCase\Command\ApproveVerification\ApproveVerificationInterface;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccount;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccount;
use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountInterface;
use Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest\RejectAccountCategoryChangeRequest;
use Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest\RejectAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Command\RejectVerification\RejectVerification;
use Source\Account\Account\Application\UseCase\Command\RejectVerification\RejectVerificationInterface;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChange;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeInterface;
use Source\Account\Account\Application\UseCase\Command\RequestVerification\RequestVerification;
use Source\Account\Account\Application\UseCase\Command\RequestVerification\RequestVerificationInterface;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccount;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocuments;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocumentsInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccount\GetAccountInterface;
use Source\Account\Account\Application\UseCase\Query\ListAccountDocuments\ListAccountDocumentsInterface;
use Source\Account\Account\Infrastructure\Query\GetAccount;
use Source\Account\Account\Infrastructure\Query\ListAccountDocuments;
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
use Source\Account\Invitation\Application\UseCase\Command\InviteMember\InviteMember;
use Source\Account\Invitation\Application\UseCase\Command\InviteMember\InviteMemberInterface;
use Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembers;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInterface;
use Source\Account\Principal\Application\UseCase\Query\ListMembers\ListMembersInterface;
use Source\Account\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\Account\Principal\Infrastructure\Query\ListMembers;
use Source\Account\Principal\Infrastructure\Query\ListPrincipalGroups;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CreateAccountInterface::class, CreateAccount::class);
        $this->app->singleton(CreatePrincipalGroupInterface::class, CreatePrincipalGroup::class);
        $this->app->singleton(DeletePrincipalGroupInterface::class, DeletePrincipalGroup::class);
        $this->app->singleton(AddPrincipalToPrincipalGroupInterface::class, AddPrincipalToPrincipalGroup::class);
        $this->app->singleton(RemovePrincipalFromPrincipalGroupInterface::class, RemovePrincipalFromPrincipalGroup::class);
        $this->app->singleton(UpdatePrincipalGroupMembersInterface::class, UpdatePrincipalGroupMembers::class);
        $this->app->singleton(ListMembersInterface::class, ListMembers::class);
        $this->app->singleton(ListPrincipalGroupsInterface::class, ListPrincipalGroups::class);
        $this->app->singleton(GrantDelegationPermissionInterface::class, GrantDelegationPermission::class);
        $this->app->singleton(RevokeDelegationPermissionInterfaceNew::class, RevokeDelegationPermissionUseCase::class);
        $this->app->singleton(DeleteAccountInterface::class, DeleteAccount::class);
        $this->app->singleton(UpdateAccountInterface::class, UpdateAccount::class);
        $this->app->singleton(GetAccountInterface::class, GetAccount::class);
        $this->app->singleton(ListAccountDocumentsInterface::class, ListAccountDocuments::class);
        $this->app->singleton(RevokeDelegationInterface::class, RevokeDelegation::class);
        $this->app->singleton(RequestDelegationInterface::class, RequestDelegation::class);
        $this->app->singleton(ApproveDelegationInterface::class, ApproveDelegation::class);
        $this->app->singleton(ApproveAffiliationInterface::class, ApproveAffiliation::class);
        $this->app->singleton(TerminateAffiliationInterface::class, TerminateAffiliation::class);
        $this->app->singleton(RequestAffiliationInterface::class, RequestAffiliation::class);
        $this->app->singleton(RejectAffiliationInterface::class, RejectAffiliation::class);

        $this->app->singleton(UploadDocumentsInterface::class, UploadDocuments::class);

        // AccountVerification
        $this->app->singleton(RequestVerificationInterface::class, RequestVerification::class);
        $this->app->singleton(RequestAccountCategoryChangeInterface::class, RequestAccountCategoryChange::class);
        $this->app->singleton(ApproveAccountCategoryChangeRequestInterface::class, ApproveAccountCategoryChangeRequest::class);
        $this->app->singleton(RejectAccountCategoryChangeRequestInterface::class, RejectAccountCategoryChangeRequest::class);
        $this->app->singleton(ApproveVerificationInterface::class, ApproveVerification::class);
        $this->app->singleton(RejectVerificationInterface::class, RejectVerification::class);

        // Invitation
        $this->app->singleton(InviteMemberInterface::class, InviteMember::class);
    }
}
