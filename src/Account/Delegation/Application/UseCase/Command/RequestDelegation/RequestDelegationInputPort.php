<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RequestDelegation;

use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface RequestDelegationInputPort
{
    public function affiliationIdentifier(): AffiliationIdentifier;

    public function delegateIdentifier(): IdentityIdentifier;

    public function delegatorIdentifier(): IdentityIdentifier;
}
