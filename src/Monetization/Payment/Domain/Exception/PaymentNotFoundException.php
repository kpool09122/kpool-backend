<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Exception;

use DomainException;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Throwable;

class PaymentNotFoundException extends DomainException
{
    public function __construct(PaymentIdentifier $paymentIdentifier, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Payment not found: %s', (string) $paymentIdentifier),
            0,
            $previous
        );
    }
}
