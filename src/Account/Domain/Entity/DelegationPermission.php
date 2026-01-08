<?php

declare(strict_types=1);

namespace Source\Account\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class DelegationPermission
{
    public function __construct(
        private DelegationPermissionIdentifier $delegationPermissionIdentifier,
        private IdentityGroupIdentifier $identityGroupIdentifier,
        private AccountIdentifier $targetAccountIdentifier,
        private AffiliationIdentifier $affiliationIdentifier,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public function delegationPermissionIdentifier(): DelegationPermissionIdentifier
    {
        return $this->delegationPermissionIdentifier;
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

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
