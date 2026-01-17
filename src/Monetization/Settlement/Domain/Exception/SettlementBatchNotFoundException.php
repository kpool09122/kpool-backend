<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Exception;

use Exception;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;

class SettlementBatchNotFoundException extends Exception
{
    public function __construct(SettlementBatchIdentifier $settlementBatchIdentifier)
    {
        parent::__construct(sprintf('Settlement batch not found: %s', (string) $settlementBatchIdentifier));
    }
}
