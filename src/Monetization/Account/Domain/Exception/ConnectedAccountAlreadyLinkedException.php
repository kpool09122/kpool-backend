<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Exception;

use DomainException;
use Throwable;

class ConnectedAccountAlreadyLinkedException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Stripe Connected Account already linked.', 0, $previous);
    }
}
