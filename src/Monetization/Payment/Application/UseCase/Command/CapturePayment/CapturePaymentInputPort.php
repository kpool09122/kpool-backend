<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\CapturePayment;

use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;

interface CapturePaymentInputPort
{
    public function paymentIdentifier(): PaymentIdentifier;
}
