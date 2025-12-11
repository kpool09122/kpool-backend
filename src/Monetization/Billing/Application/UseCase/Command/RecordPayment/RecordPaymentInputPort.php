<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Application\UseCase\Command\RecordPayment;

use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;

interface RecordPaymentInputPort
{
    public function invoiceIdentifier(): InvoiceIdentifier;

    public function paymentIdentifier(): PaymentIdentifier;
}
