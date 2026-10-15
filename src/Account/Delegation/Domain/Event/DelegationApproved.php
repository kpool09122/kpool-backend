<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Event;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

readonly class DelegationApproved
{
    public function __construct(
        public DelegationIdentifier $delegationIdentifier,
        public AccountIdentifier $requestedByAccountIdentifier,
        public AccountIdentifier $approverAccountIdentifier,
        public string $approverAccountName,
    ) {
    }
}
