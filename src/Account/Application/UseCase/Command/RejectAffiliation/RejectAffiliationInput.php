<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RejectAffiliation;

use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class RejectAffiliationInput implements RejectAffiliationInputPort
{
    public function __construct(
        private AffiliationIdentifier $affiliationIdentifier,
        private AccountIdentifier $rejectorAccountIdentifier,
    ) {
    }

    public function affiliationIdentifier(): AffiliationIdentifier
    {
        return $this->affiliationIdentifier;
    }

    public function rejectorAccountIdentifier(): AccountIdentifier
    {
        return $this->rejectorAccountIdentifier;
    }
}
