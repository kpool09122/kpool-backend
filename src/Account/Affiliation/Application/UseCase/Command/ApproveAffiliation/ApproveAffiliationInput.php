<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class ApproveAffiliationInput implements ApproveAffiliationInputPort
{
    public function __construct(
        private AffiliationIdentifier $affiliationIdentifier,
        private AccountIdentifier $approverAccountIdentifier,
    ) {
    }

    public function affiliationIdentifier(): AffiliationIdentifier
    {
        return $this->affiliationIdentifier;
    }

    public function approverAccountIdentifier(): AccountIdentifier
    {
        return $this->approverAccountIdentifier;
    }
}
