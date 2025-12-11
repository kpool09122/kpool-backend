<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Shared\Domain\ValueObject\Money;

interface AuthorizePaymentInputPort
{
    public function money(): Money;

    public function paymentMethod(): PaymentMethod;
}
