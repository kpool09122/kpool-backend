<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\GrantDelegationPermission;

use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class GrantDelegationPermissionInput implements GrantDelegationPermissionInputPort
{
    public function __construct(
        private IdentityGroupIdentifier $identityGroupIdentifier,
        private AccountIdentifier $targetAccountIdentifier,
        private AffiliationIdentifier $affiliationIdentifier,
    ) {
    }

    public function identityGroupIdentifier(): IdentityGroupIdentifier
    {
        return $this->identityGroupIdentifier;
    }

    public function targetAccountIdentifier(): AccountIdentifier
    {
        return $this->targetAccountIdentifier;
    }

    public function affiliationIdentifier(): AffiliationIdentifier
    {
        return $this->affiliationIdentifier;
    }
}
