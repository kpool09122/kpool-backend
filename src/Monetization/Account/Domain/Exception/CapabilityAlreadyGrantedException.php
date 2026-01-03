<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Exception;

use Exception;
use Source\Monetization\Account\Domain\ValueObject\Capability;

class CapabilityAlreadyGrantedException extends Exception
{
    public function __construct(Capability $capability)
    {
        parent::__construct("Capability '{$capability->value}' is already granted.", 0);
    }
}
