<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Support\ServiceProvider;
use Source\Account\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliation;
use Source\Account\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationInterface;
use Source\Account\Application\UseCase\Command\ApproveDelegation\ApproveDelegation;
use Source\Account\Application\UseCase\Command\ApproveDelegation\ApproveDelegationInterface;
use Source\Account\Application\UseCase\Command\CreateAccount\CreateAccount;
use Source\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Application\UseCase\Command\DeleteAccount\DeleteAccount;
use Source\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountInterface;
use Source\Account\Application\UseCase\Command\RejectAffiliation\RejectAffiliation;
use Source\Account\Application\UseCase\Command\RejectAffiliation\RejectAffiliationInterface;
use Source\Account\Application\UseCase\Command\RequestAffiliation\RequestAffiliation;
use Source\Account\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInterface;
use Source\Account\Application\UseCase\Command\RequestDelegation\RequestDelegation;
use Source\Account\Application\UseCase\Command\RequestDelegation\RequestDelegationInterface;
use Source\Account\Application\UseCase\Command\RevokeDelegation\RevokeDelegation;
use Source\Account\Application\UseCase\Command\RevokeDelegation\RevokeDelegationInterface;
use Source\Account\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliation;
use Source\Account\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliationInterface;
use Source\Account\Application\UseCase\Command\WithdrawFromMembership\WithdrawFromMembership;
use Source\Account\Application\UseCase\Command\WithdrawFromMembership\WithdrawFromMembershipInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CreateAccountInterface::class, CreateAccount::class);
        $this->app->singleton(WithdrawFromMembershipInterface::class, WithdrawFromMembership::class);
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
