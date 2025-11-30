<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Support\ServiceProvider;
use Source\Account\Application\UseCase\Command\CreateAccount\CreateAccount;
use Source\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Application\UseCase\Command\WithdrawFromMembership\WithdrawFromMembership;
use Source\Account\Application\UseCase\Command\WithdrawFromMembership\WithdrawFromMembershipInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CreateAccountInterface::class, CreateAccount::class);
        $this->app->singleton(WithdrawFromMembershipInterface::class, WithdrawFromMembership::class);
    }
}
