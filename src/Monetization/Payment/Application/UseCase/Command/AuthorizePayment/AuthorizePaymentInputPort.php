<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;

interface AuthorizePaymentInputPort
{
    public function orderIdentifier(): OrderIdentifier;

    public function money(): Money;

    public function paymentMethod(): PaymentMethod;
}
