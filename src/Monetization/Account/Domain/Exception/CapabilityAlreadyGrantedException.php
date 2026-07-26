<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Exception;

use DomainException;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Throwable;

class CapabilityAlreadyGrantedException extends DomainException
{
    public function __construct(
        Capability $capability,
        ?Throwable $previous = null,
    ) {
        parent::__construct("Capability '{$capability->value}' is already granted.", 0, $previous);
    }
}
