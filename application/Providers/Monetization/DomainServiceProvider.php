<?php

declare(strict_types=1);

namespace Application\Providers\Monetization;

use Illuminate\Support\ServiceProvider;
use Source\Monetization\Payment\Domain\Factory\PaymentFactory;
use Source\Monetization\Payment\Domain\Factory\PaymentFactoryInterface;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(PaymentFactoryInterface::class, PaymentFactory::class);
    }
}
