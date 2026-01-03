<?php

declare(strict_types=1);

namespace Application\Providers\Monetization;

use Illuminate\Support\ServiceProvider;
use Source\Monetization\Account\Domain\Factory\MonetizationAccountFactoryInterface;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Infrastructure\Factory\MonetizationAccountFactory;
use Source\Monetization\Account\Infrastructure\Repository\MonetizationAccountRepository;
use Source\Monetization\Billing\Domain\Factory\InvoiceFactoryInterface;
use Source\Monetization\Billing\Domain\Repository\InvoiceRepositoryInterface;
use Source\Monetization\Billing\Domain\Service\TaxDocumentPolicyService;
use Source\Monetization\Billing\Domain\Service\TaxDocumentPolicyServiceInterface;
use Source\Monetization\Billing\Infrastructure\Factory\InvoiceFactory;
use Source\Monetization\Billing\Infrastructure\Repository\InvoiceRepository;
use Source\Monetization\Payment\Domain\Factory\PaymentFactoryInterface;
use Source\Monetization\Payment\Domain\Repository\PaymentRepositoryInterface;
use Source\Monetization\Payment\Domain\Service\PaymentGatewayInterface;
use Source\Monetization\Payment\Infrastructure\Factory\PaymentFactory;
use Source\Monetization\Payment\Infrastructure\Repository\PaymentRepository;
use Source\Monetization\Payment\Infrastructure\Service\PaymentGateway;
use Source\Monetization\Settlement\Domain\Factory\SettlementBatchFactoryInterface;
use Source\Monetization\Settlement\Domain\Factory\TransferFactoryInterface;
use Source\Monetization\Settlement\Domain\Service\FeeCalculatorService;
use Source\Monetization\Settlement\Domain\Service\FeeCalculatorServiceInterface;
use Source\Monetization\Settlement\Domain\Service\SettlementService;
use Source\Monetization\Settlement\Domain\Service\SettlementServiceInterface;
use Source\Monetization\Settlement\Infrastructure\Factory\SettlementBatchFactory;
use Source\Monetization\Settlement\Infrastructure\Factory\TransferFactory;
use Source\Monetization\Shared\Service\PaymentMatcherService;
use Source\Monetization\Shared\Service\PaymentMatcherServiceInterface;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Account
        $this->app->singleton(MonetizationAccountFactoryInterface::class, MonetizationAccountFactory::class);
        $this->app->singleton(MonetizationAccountRepositoryInterface::class, MonetizationAccountRepository::class);

        // Payment
        $this->app->singleton(PaymentFactoryInterface::class, PaymentFactory::class);
        $this->app->singleton(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->singleton(PaymentGatewayInterface::class, PaymentGateway::class);
        $this->app->singleton(PaymentMatcherServiceInterface::class, PaymentMatcherService::class);
        $this->app->singleton(TaxDocumentPolicyServiceInterface::class, TaxDocumentPolicyService::class);
        $this->app->singleton(InvoiceFactoryInterface::class, InvoiceFactory::class);
        $this->app->singleton(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->singleton(SettlementBatchFactoryInterface::class, SettlementBatchFactory::class);
        $this->app->singleton(TransferFactoryInterface::class, TransferFactory::class);
        $this->app->singleton(FeeCalculatorServiceInterface::class, FeeCalculatorService::class);
        $this->app->singleton(SettlementServiceInterface::class, SettlementService::class);
    }
}
