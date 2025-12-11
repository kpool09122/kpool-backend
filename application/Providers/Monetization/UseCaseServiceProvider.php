<?php

declare(strict_types=1);

namespace Application\Providers\Monetization;

use Illuminate\Support\ServiceProvider;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoice;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoiceInterface;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePayment;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CreateInvoiceInterface::class, CreateInvoice::class);
        $this->app->singleton(AuthorizePaymentInterface::class, AuthorizePayment::class);
    }
}
