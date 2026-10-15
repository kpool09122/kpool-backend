<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Domain\Event;

use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AffiliationApproved
{
    public function __construct(
        public AffiliationIdentifier $affiliationIdentifier,
        public AccountIdentifier $requestedByAccountIdentifier,
        public AccountIdentifier $approverAccountIdentifier,
        public string $approverAccountName,
    ) {
    }
}
