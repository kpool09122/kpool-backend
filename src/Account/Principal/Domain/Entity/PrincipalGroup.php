<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Principal\Domain\Exception\PrincipalAlreadyMemberException;
use Source\Account\Principal\Domain\Exception\PrincipalNotMemberException;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PrincipalGroup
{
    /** @var array<string, Principal> */
    private array $members = [];

    public function __construct(
        private readonly PrincipalGroupIdentifier $principalGroupIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly string $name,
        private readonly AccountRole $role,
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
     * @return array<string, Principal>
     */
    public function members(): array
    {
        return $this->members;
    }

    public function memberCount(): int
    {
        return count($this->members);
    }

    public function hasMember(Principal|IdentityIdentifier $principal): bool
    {
        $principal = $this->normalizePrincipal($principal);

        return isset($this->members[(string) $principal->principalIdentifier()]);
    }

    public function addMember(Principal|IdentityIdentifier $principal): void
    {
        $principal = $this->normalizePrincipal($principal);

        if ($this->hasMember($principal)) {
            throw new PrincipalAlreadyMemberException();
        }

        $this->members[(string) $principal->principalIdentifier()] = $principal;
    }

    public function removeMember(Principal|IdentityIdentifier $principal): void
    {
        $principal = $this->normalizePrincipal($principal);

        if (! $this->hasMember($principal)) {
            throw new PrincipalNotMemberException();
        }

        unset($this->members[(string) $principal->principalIdentifier()]);
    }

    private function normalizePrincipal(Principal|IdentityIdentifier $principal): Principal
    {
        if ($principal instanceof Principal) {
            return $principal;
        }

        return new Principal($principal);
    }
}
