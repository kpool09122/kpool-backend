<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Exception;

use DomainException;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Throwable;

class SettlementBatchNotFoundException extends DomainException
{
    public function __construct(
        SettlementBatchIdentifier $settlementBatchIdentifier,
        ?Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Settlement batch not found: %s', (string) $settlementBatchIdentifier), 0, $previous);
    }
}
