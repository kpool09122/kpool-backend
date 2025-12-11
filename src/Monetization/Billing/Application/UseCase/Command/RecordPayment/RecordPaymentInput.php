<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Application\UseCase\Command\RecordPayment;

use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;

readonly class RecordPaymentInput implements RecordPaymentInputPort
{
    public function __construct(
        private InvoiceIdentifier $invoiceIdentifier,
        private PaymentIdentifier $paymentIdentifier,
    ) {
    }

    public function invoiceIdentifier(): InvoiceIdentifier
    {
        return $this->invoiceIdentifier;
    }

    public function paymentIdentifier(): PaymentIdentifier
    {
        return $this->paymentIdentifier;
    }
}
