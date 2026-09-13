<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AccountContext
{
    /** @param array<int, array<string, mixed>> $accountPolicies */
    public function __construct(
        private Principal $principal,
        private AccountType $accountType,
        private AccountCategory $accountCategory,
        private array $accountPolicies = [],
        private ?IdentityIdentifier $originalIdentityIdentifier = null,
        private ?AccountIdentifier $originalAccountIdentifier = null,
        private ?PrincipalIdentifier $originalPrincipalIdentifier = null,
        private ?DelegationIdentifier $delegationIdentifier = null,
    ) {
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function accountType(): AccountType
    {
        return $this->accountType;
    }

    /** @return array<int, array<string, mixed>> */
    public function accountPolicies(): array
    {
        return $this->accountPolicies;
    }

    public function accountCategory(): AccountCategory
    {
        return $this->accountCategory;
    }

    public function originalIdentityIdentifier(): IdentityIdentifier
    {
        return $this->originalIdentityIdentifier ?? $this->principal->identityIdentifier();
    }

    public function originalAccountIdentifier(): AccountIdentifier
    {
        return $this->originalAccountIdentifier ?? $this->principal->accountIdentifier();
    }

    public function originalPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->originalPrincipalIdentifier ?? $this->principal->principalIdentifier();
    }

    public function delegationIdentifier(): ?DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }
}
