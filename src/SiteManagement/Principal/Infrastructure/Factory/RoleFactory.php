<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Role;
use Source\SiteManagement\Principal\Domain\Factory\RoleFactoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

readonly class RoleFactory implements RoleFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    /** @param PolicyIdentifier[] $policies */
    public function create(string $name, array $policies, ?AccountIdentifier $accountIdentifier = null): Role
    {
        return new Role(new RoleIdentifier($this->uuidGenerator->generate()), $name, $policies, $accountIdentifier);
    }
}
