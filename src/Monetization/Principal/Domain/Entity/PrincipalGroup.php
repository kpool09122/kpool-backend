<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Domain\Entity;

use InvalidArgumentException;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\PrincipalGroupDescription;

class PrincipalGroup
{
    /** 利用開始時に Owner ロールを割り当て、Account オーナーを所属させる。 */
    public const string OWNER_GROUP_NAME = 'Owners';

    /** 利用開始時に General ロールを割り当て、オーナーを含む全利用者を所属させる既定グループ。 */
    public const string GENERAL_GROUP_NAME = 'Default';

    /**
     * @param RoleIdentifier[] $roles
     * @param PrincipalIdentifier[] $members
     */
    public function __construct(
        private readonly PrincipalGroupIdentifier $principalGroupIdentifier,
        private readonly string $name,
        private array $roles,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly bool $isDefault,
        private ?PrincipalGroupDescription $description,
        private array $members,
    ) {
        $this->replaceMembers($members);
    }

    public function principalGroupIdentifier(): PrincipalGroupIdentifier
    {
        return $this->principalGroupIdentifier;
    }

    public function description(): ?PrincipalGroupDescription
    {
        return $this->description;
    }

    public function setDescription(?PrincipalGroupDescription $description): void
    {
        $this->description = $description;
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

    public function hasRole(RoleIdentifier $roleIdentifier): bool
    {
        return array_any($this->roles, static fn (RoleIdentifier $role): bool => (string) $role === (string) $roleIdentifier);
    }

    public function addRole(Role $role): void
    {
        if ($role->accountIdentifier() !== null && (string) $role->accountIdentifier() !== (string) $this->accountIdentifier) {
            throw new InvalidArgumentException('A principal group cannot attach a role from another account.');
        }
        if (! $this->hasRole($role->roleIdentifier())) {
            $this->roles[] = $role->roleIdentifier();
        }
    }

    public function hasMember(PrincipalIdentifier $principalIdentifier): bool
    {
        return isset($this->members[(string) $principalIdentifier]);
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
