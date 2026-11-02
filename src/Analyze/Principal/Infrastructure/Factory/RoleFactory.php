<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Infrastructure\Factory;

use Source\Analyze\Principal\Domain\Entity\Role;
use Source\Analyze\Principal\Domain\Factory\RoleFactoryInterface;
use Source\Analyze\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

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
