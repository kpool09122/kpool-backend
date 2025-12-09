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
use Source\Monetization\Settlement\Domain\Factory\SettlementBatchFactory;
use Source\Monetization\Settlement\Domain\Factory\SettlementBatchFactoryInterface;
use Source\Monetization\Settlement\Domain\Factory\TransferFactory;
use Source\Monetization\Settlement\Domain\Factory\TransferFactoryInterface;
use Source\Monetization\Settlement\Domain\Service\FeeCalculatorService;
use Source\Monetization\Settlement\Domain\Service\FeeCalculatorServiceInterface;
use Source\Monetization\Settlement\Domain\Service\SettlementService;
use Source\Monetization\Settlement\Domain\Service\SettlementServiceInterface;
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
        $this->app->singleton(SettlementBatchFactoryInterface::class, SettlementBatchFactory::class);
        $this->app->singleton(TransferFactoryInterface::class, TransferFactory::class);
        $this->app->singleton(FeeCalculatorServiceInterface::class, FeeCalculatorService::class);
        $this->app->singleton(SettlementServiceInterface::class, SettlementService::class);
    }
}
