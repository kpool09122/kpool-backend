<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
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
        private readonly ?AccountIdentifier $accountIdentifier,
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
        return $this->accountIdentifier === null;
    }

    public function accountIdentifier(): ?AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function addPolicy(Policy $policy): void
    {
        if (! $this->canAttachPolicy($policy)) {
            throw new InvalidArgumentException('Role and policy account scopes are incompatible.');
        }

        if ($this->hasPolicy($policy->policyIdentifier())) {
            return;
        }

        $this->policies[] = $policy->policyIdentifier();
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
        return array_any($this->policies, fn ($policy) => (string) $policy === (string) $policyIdentifier);
    }

    private function canAttachPolicy(Policy $policy): bool
    {
        if ($this->accountIdentifier === null) {
            return $policy->accountIdentifier() === null;
        }

        return $policy->accountIdentifier() === null
            || (string) $policy->accountIdentifier() === (string) $this->accountIdentifier;
    }
}
