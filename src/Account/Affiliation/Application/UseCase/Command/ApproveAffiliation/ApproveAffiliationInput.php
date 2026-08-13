<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;

readonly class ApproveAffiliationInput implements ApproveAffiliationInputPort
{
    public function __construct(
        private AffiliationIdentifier $affiliationIdentifier,
        private Principal $principal,
    ) {
    }

    public function affiliationIdentifier(): AffiliationIdentifier
    {
        return $this->affiliationIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
