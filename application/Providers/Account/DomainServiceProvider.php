<?php

declare(strict_types=1);

namespace Application\Providers\Account;

use Illuminate\Support\ServiceProvider;
use Source\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Infrastructure\Factory\AccountFactory;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(AccountFactoryInterface::class, AccountFactory::class);
    }
}
