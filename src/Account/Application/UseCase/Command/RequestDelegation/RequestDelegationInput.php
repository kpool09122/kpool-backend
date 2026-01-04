<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RequestDelegation;

use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class RequestDelegationInput implements RequestDelegationInputPort
{
    public function __construct(
        private AffiliationIdentifier $affiliationIdentifier,
        private IdentityIdentifier $delegateIdentifier,
        private IdentityIdentifier $delegatorIdentifier,
    ) {
    }

    public function affiliationIdentifier(): AffiliationIdentifier
    {
        return $this->affiliationIdentifier;
    }

    public function delegateIdentifier(): IdentityIdentifier
    {
        return $this->delegateIdentifier;
    }

    public function delegatorIdentifier(): IdentityIdentifier
    {
        return $this->delegatorIdentifier;
    }
}
