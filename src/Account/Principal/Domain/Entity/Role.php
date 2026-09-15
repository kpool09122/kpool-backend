<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Entity;

use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class Role
{
    public const string OWNER = 'Owner';
    public const string ADMIN = 'Admin';
    public const string OPERATIONS = 'Operations';
    public const string DELEGATION_ACCOUNT_SWITCHER = 'DelegationAccountSwitcher';

    /**
     * @param PolicyIdentifier[] $policies
     */
    public function __construct(
        private readonly RoleIdentifier $roleIdentifier,
        private readonly string $name,
        private array $policies,
        private readonly ?AccountIdentifier $accountIdentifier,
    ) {
    }

    public function roleIdentifier(): RoleIdentifier
    {
        return $this->roleIdentifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return PolicyIdentifier[]
     */
    public function policies(): array
    {
        return $this->policies;
    }

    public function isSystemRole(): bool
    {
        return $this->accountIdentifier === null;
    }

    public function accountIdentifier(): ?AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function canAttachPolicy(Policy $policy): bool
    {
        if ($this->accountIdentifier === null) {
            return $policy->accountIdentifier() === null;
        }

        return $policy->accountIdentifier() === null
            || (string) $policy->accountIdentifier() === (string) $this->accountIdentifier;
    }

    public function addPolicy(PolicyIdentifier $policyIdentifier): void
    {
        if ($this->hasPolicy($policyIdentifier)) {
            return;
        }

        $this->policies[] = $policyIdentifier;
    }

    public function removePolicy(PolicyIdentifier $policyIdentifier): void
    {
        $this->policies = array_values(array_filter(
            $this->policies,
            static fn (PolicyIdentifier $p) => (string) $p !== (string) $policyIdentifier
        ));
    }

    public function hasPolicy(PolicyIdentifier $policyIdentifier): bool
    {
        return array_any($this->policies, static fn (PolicyIdentifier $policy) => (string) $policy === (string) $policyIdentifier);
    }
}
