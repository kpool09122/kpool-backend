<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Entity;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

class PrincipalGroup
{
    /** @var PrincipalIdentifier[] */
    private array $members = [];

    /** @param RoleIdentifier[] $roles */
    public function __construct(
        private readonly PrincipalGroupIdentifier $principalGroupIdentifier,
        private readonly string $name,
        private readonly array $roles,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly bool $isDefault = false,
    ) {
    }

    public function principalGroupIdentifier(): PrincipalGroupIdentifier
    {
        return $this->principalGroupIdentifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return RoleIdentifier[] */
    public function roles(): array
    {
        return $this->roles;
    }

    /** @return PrincipalIdentifier[] */
    public function members(): array
    {
        return $this->members;
    }

    public function addMember(PrincipalIdentifier $principalIdentifier): void
    {
        $this->members[(string) $principalIdentifier] = $principalIdentifier;
    }

    /** @param PrincipalIdentifier[] $members */
    public function replaceMembers(array $members): void
    {
        $this->members = [];
        foreach ($members as $member) {
            $this->addMember($member);
        }
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
