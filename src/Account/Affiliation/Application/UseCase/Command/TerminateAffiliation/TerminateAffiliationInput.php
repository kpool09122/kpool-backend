<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation;

use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class TerminateAffiliationInput implements TerminateAffiliationInputPort
{
    public function __construct(
        private AffiliationIdentifier $affiliationIdentifier,
        private AccountIdentifier $terminatorAccountIdentifier,
    ) {
    }

    public function affiliationIdentifier(): AffiliationIdentifier
    {
        return $this->affiliationIdentifier;
    }

    public function terminatorAccountIdentifier(): AccountIdentifier
    {
        return $this->terminatorAccountIdentifier;
    }
}
