<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Exception;

use DomainException;
use Throwable;

class ConnectedAccountNotLinkedException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Stripe Connected Account is not linked.', 0, $previous);
    }
}
