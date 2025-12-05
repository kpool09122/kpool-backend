<?php

declare(strict_types=1);

namespace Application\Providers\Monetization;

use Illuminate\Support\ServiceProvider;
use Source\Monetization\Billing\Domain\Factory\InvoiceFactory;
use Source\Monetization\Billing\Domain\Factory\InvoiceFactoryInterface;
use Source\Monetization\Billing\Domain\Service\TaxDocumentPolicyService;
use Source\Monetization\Billing\Domain\Service\TaxDocumentPolicyServiceInterface;
use Source\Monetization\Payment\Domain\Factory\PaymentFactory;
use Source\Monetization\Payment\Domain\Factory\PaymentFactoryInterface;
use Source\Monetization\Shared\Service\PaymentMatcherService;
use Source\Monetization\Shared\Service\PaymentMatcherServiceInterface;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(PaymentFactoryInterface::class, PaymentFactory::class);
        $this->app->singleton(PaymentMatcherServiceInterface::class, PaymentMatcherService::class);
        $this->app->singleton(TaxDocumentPolicyServiceInterface::class, TaxDocumentPolicyService::class);
        $this->app->singleton(InvoiceFactoryInterface::class, InvoiceFactory::class);
    }
}
