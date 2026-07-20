<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Entity;

use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;

class Role
{
    /**
     * @param PolicyIdentifier[] $policies
     */
    public function __construct(
        private readonly AccountRole $role,
        private array $policies,
    ) {
    }

    public function role(): AccountRole
    {
        return $this->role;
    }

    /**
     * @return PolicyIdentifier[]
     */
    public function policies(): array
    {
        return $this->policies;
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
