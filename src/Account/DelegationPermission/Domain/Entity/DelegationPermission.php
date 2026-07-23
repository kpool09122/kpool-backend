<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Domain\Entity;

use DateTimeImmutable;
use Source\Account\DelegationPermission\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class DelegationPermission
{
    public function __construct(
        private DelegationPermissionIdentifier $delegationPermissionIdentifier,
        private PrincipalGroupIdentifier $principalGroupIdentifier,
        private AccountIdentifier $targetAccountIdentifier,
        private AffiliationIdentifier $affiliationIdentifier,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public function delegationPermissionIdentifier(): DelegationPermissionIdentifier
    {
        return $this->delegationPermissionIdentifier;
    }

    public function principalGroupIdentifier(): PrincipalGroupIdentifier
    {
        return $this->principalGroupIdentifier;
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
