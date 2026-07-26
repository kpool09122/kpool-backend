<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Exception;

use DomainException;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Throwable;

class TransferNotFoundException extends DomainException
{
    public function __construct(
        TransferIdentifier $transferIdentifier,
        ?Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Transfer not found: %s', (string) $transferIdentifier), 0, $previous);
    }
}
