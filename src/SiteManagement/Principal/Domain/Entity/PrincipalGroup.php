<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Entity;

use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

class PrincipalGroup
{
    public const string GENERAL = '69200000-0000-7000-8000-000000000001';
    public const string ADMINISTRATOR = '69200000-0000-7000-8000-000000000002';

    /** @param RoleIdentifier[] $roles */
    public function __construct(private readonly PrincipalGroupIdentifier $principalGroupIdentifier, private readonly string $name, private readonly array $roles)
    {
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
    /** @var PrincipalIdentifier[] */ private array $members = [];

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
}
