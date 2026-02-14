<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Exception;

use DomainException;
use Throwable;

class PaymentCustomerAlreadyLinkedException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Stripe Customer already linked.', 0, $previous);
    }
}
