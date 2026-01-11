<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class IdentityGroup
{
    /** @var array<string, IdentityIdentifier> */
    private array $members = [];

    public function __construct(
        private readonly IdentityGroupIdentifier $identityGroupIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly string $name,
        private readonly AccountRole $role,
        private readonly bool $isDefault,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function identityGroupIdentifier(): IdentityGroupIdentifier
    {
        return $this->identityGroupIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function role(): AccountRole
    {
        return $this->role;
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
     * @return array<string, IdentityIdentifier>
     */
    public function members(): array
    {
        return $this->members;
    }

    public function memberCount(): int
    {
        return count($this->members);
    }

    public function hasMember(IdentityIdentifier $identityIdentifier): bool
    {
        return isset($this->members[(string) $identityIdentifier]);
    }

    public function addMember(IdentityIdentifier $identityIdentifier): void
    {
        if ($this->hasMember($identityIdentifier)) {
            throw new DomainException('Identity is already a member of this group.');
        }

        $this->members[(string) $identityIdentifier] = $identityIdentifier;
    }

    public function removeMember(IdentityIdentifier $identityIdentifier): void
    {
        if (! $this->hasMember($identityIdentifier)) {
            throw new DomainException('Identity is not a member of this group.');
        }

        unset($this->members[(string) $identityIdentifier]);
    }
}
