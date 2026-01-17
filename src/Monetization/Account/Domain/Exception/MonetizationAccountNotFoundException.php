<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Exception;

use Exception;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;

class MonetizationAccountNotFoundException extends Exception
{
    public function __construct(MonetizationAccountIdentifier $monetizationAccountIdentifier)
    {
        parent::__construct(sprintf('Monetization account not found: %s', (string) $monetizationAccountIdentifier));
    }
}
