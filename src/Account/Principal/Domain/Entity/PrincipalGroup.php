<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Principal\Domain\Exception\PrincipalAlreadyMemberException;
use Source\Account\Principal\Domain\Exception\PrincipalNotMemberException;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

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
            throw new PrincipalAlreadyMemberException();
        }

        $this->members[(string) $principalIdentifier] = $principalIdentifier;
    }

    public function removeMember(PrincipalIdentifier $principalIdentifier): void
    {
        if (! $this->hasMember($principalIdentifier)) {
            throw new PrincipalNotMemberException();
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
        return array_any($this->roles, static fn (RoleIdentifier $role) => (string) $role === (string) $roleIdentifier);
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
            static fn (RoleIdentifier $role) => (string) $role !== (string) $roleIdentifier
        ));
    }
}
