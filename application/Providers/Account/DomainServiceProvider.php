<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Support\ServiceProvider;
use Source\Account\Domain\Factory\AccountFactory;
use Source\Account\Domain\Factory\AccountFactoryInterface;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AccountFactoryInterface::class, AccountFactory::class);
    }
}
