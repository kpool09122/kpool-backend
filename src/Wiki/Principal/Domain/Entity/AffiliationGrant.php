<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantType;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

readonly class AffiliationGrant
{
    public function __construct(
        private AffiliationGrantIdentifier $affiliationGrantIdentifier,
        private AffiliationIdentifier $affiliationIdentifier,
        private PolicyIdentifier $policyIdentifier,
        private RoleIdentifier $roleIdentifier,
        private PrincipalGroupIdentifier $principalGroupIdentifier,
        private AffiliationGrantType $type,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public function affiliationGrantIdentifier(): AffiliationGrantIdentifier
    {
        return $this->affiliationGrantIdentifier;
    }

    public function affiliationIdentifier(): AffiliationIdentifier
    {
        return $this->affiliationIdentifier;
    }

    public function policyIdentifier(): PolicyIdentifier
    {
        return $this->policyIdentifier;
    }

    public function roleIdentifier(): RoleIdentifier
    {
        return $this->roleIdentifier;
    }

    public function principalGroupIdentifier(): PrincipalGroupIdentifier
    {
        return $this->principalGroupIdentifier;
    }

    public function type(): AffiliationGrantType
    {
        return $this->type;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
