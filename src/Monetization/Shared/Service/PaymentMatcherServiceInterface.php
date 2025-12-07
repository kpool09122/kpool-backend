<?php

declare(strict_types=1);

namespace Source\Monetization\Shared\Service;

use DateTimeImmutable;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Payment\Domain\Entity\Payment;

interface PaymentMatcherServiceInterface
{
    public function match(Invoice $invoice, Payment $payment, DateTimeImmutable $paidAt): void;
}
