<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Exception;

use DomainException;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Throwable;

class MonetizationAccountNotFoundException extends DomainException
{
    public function __construct(
        MonetizationAccountIdentifier|ConnectedAccountId $identifier,
        ?Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Monetization account not found: %s', (string) $identifier), 0, $previous);
    }
}
