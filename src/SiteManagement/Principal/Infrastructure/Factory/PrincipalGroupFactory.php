<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

readonly class PrincipalGroupFactory implements PrincipalGroupFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    /** @param RoleIdentifier[] $roles */
    public function create(string $name, array $roles, AccountIdentifier $accountIdentifier, bool $isDefault = false): PrincipalGroup
    {
        return new PrincipalGroup(new PrincipalGroupIdentifier($this->uuidGenerator->generate()), $name, $roles, $accountIdentifier, $isDefault);
    }
}
