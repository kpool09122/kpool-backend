<?php

declare(strict_types=1);

namespace Source\Monetization\Shared\Exception;

use DomainException;
use Throwable;

class PaymentNotCapturedForMatchingException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Payment must be captured before matching to invoice.', 0, $previous);
    }
}
