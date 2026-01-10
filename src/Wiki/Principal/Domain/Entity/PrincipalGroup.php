<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class PrincipalGroup
{
    /** @var array<string, PrincipalIdentifier> */
    private array $members = [];

    /** @var RoleIdentifier[] */
    private array $roles = [];

    public function __construct(
        private readonly PrincipalGroupIdentifier $principalGroupIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly string $name,
        private readonly bool $isDefault,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function principalGroupIdentifier(): PrincipalGroupIdentifier
    {
        return $this->principalGroupIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return array<string, PrincipalIdentifier>
     */
    public function members(): array
    {
        return $this->members;
    }

    public function memberCount(): int
    {
        return count($this->members);
    }

    public function hasMember(PrincipalIdentifier $principalIdentifier): bool
    {
        return isset($this->members[(string) $principalIdentifier]);
    }

    public function addMember(PrincipalIdentifier $principalIdentifier): void
    {
        if ($this->hasMember($principalIdentifier)) {
            throw new DomainException('Principal is already a member of this group.');
        }

        $this->members[(string) $principalIdentifier] = $principalIdentifier;
    }

    public function removeMember(PrincipalIdentifier $principalIdentifier): void
    {
        if (! $this->hasMember($principalIdentifier)) {
            throw new DomainException('Principal is not a member of this group.');
        }

        unset($this->members[(string) $principalIdentifier]);
    }

    /**
     * @return RoleIdentifier[]
     */
    public function roles(): array
    {
        return $this->roles;
    }

    public function hasRole(RoleIdentifier $roleIdentifier): bool
    {
        foreach ($this->roles as $role) {
            if ((string) $role === (string) $roleIdentifier) {
                return true;
            }
        }

        return false;
    }

    public function addRole(RoleIdentifier $roleIdentifier): void
    {
        if ($this->hasRole($roleIdentifier)) {
            return;
        }

        $this->roles[] = $roleIdentifier;
    }

    public function removeRole(RoleIdentifier $roleIdentifier): void
    {
        $this->roles = array_values(array_filter(
            $this->roles,
            fn (RoleIdentifier $r) => (string) $r !== (string) $roleIdentifier
        ));
    }
}
