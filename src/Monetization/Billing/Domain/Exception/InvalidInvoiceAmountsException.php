<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\Exception;

use DomainException;
use Throwable;

class InvalidInvoiceAmountsException extends DomainException
{
    public function __construct(
        string $message = 'Invoice amounts are invalid.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
