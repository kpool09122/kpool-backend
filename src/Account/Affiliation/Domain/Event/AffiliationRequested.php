<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Domain\Event;

use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AffiliationRequested
{
    public function __construct(
        public AffiliationIdentifier $affiliationIdentifier,
        public AccountIdentifier $targetAccountIdentifier,
        public AccountIdentifier $agencyAccountIdentifier,
        public AccountIdentifier $talentAccountIdentifier,
        public AccountIdentifier $requestedBy,
    ) {
    }
}
