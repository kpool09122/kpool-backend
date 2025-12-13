<?php

declare(strict_types=1);

namespace Application\Providers\Monetization;

use Illuminate\Support\ServiceProvider;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoice;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoiceInterface;
use Source\Monetization\Billing\Application\UseCase\Command\RecordPayment\RecordPayment;
use Source\Monetization\Billing\Application\UseCase\Command\RecordPayment\RecordPaymentInterface;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePayment;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInterface;
use Source\Monetization\Payment\Application\UseCase\Command\CapturePayment\CapturePayment;
use Source\Monetization\Payment\Application\UseCase\Command\CapturePayment\CapturePaymentInterface;
use Source\Monetization\Payment\Application\UseCase\Command\RefundPayment\RefundPayment;
use Source\Monetization\Payment\Application\UseCase\Command\RefundPayment\RefundPaymentInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CreateInvoiceInterface::class, CreateInvoice::class);
        $this->app->singleton(RecordPaymentInterface::class, RecordPayment::class);
        $this->app->singleton(AuthorizePaymentInterface::class, AuthorizePayment::class);
        $this->app->singleton(CapturePaymentInterface::class, CapturePayment::class);
        $this->app->singleton(RefundPaymentInterface::class, RefundPayment::class);
    }
}
