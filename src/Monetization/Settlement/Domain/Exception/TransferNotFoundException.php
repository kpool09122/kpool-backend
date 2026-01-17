<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Exception;

use Exception;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;

class TransferNotFoundException extends Exception
{
    public function __construct(TransferIdentifier $transferIdentifier)
    {
        parent::__construct(sprintf('Transfer not found: %s', (string) $transferIdentifier));
    }
}
