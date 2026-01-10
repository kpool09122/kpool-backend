<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

class Role
{
    /**
     * @param PolicyIdentifier[] $policies
     */
    public function __construct(
        private readonly RoleIdentifier $roleIdentifier,
        private readonly string $name,
        private array $policies,
        private readonly bool $isSystemRole,
        private readonly DateTimeImmutable $createdAt,
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
        return $this->isSystemRole;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
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
            fn (PolicyIdentifier $p) => (string) $p !== (string) $policyIdentifier
        ));
    }

    public function hasPolicy(PolicyIdentifier $policyIdentifier): bool
    {
        foreach ($this->policies as $policy) {
            if ((string) $policy === (string) $policyIdentifier) {
                return true;
            }
        }

        return false;
    }
}
