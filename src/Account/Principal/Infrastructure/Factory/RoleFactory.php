<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Factory;

use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Factory\RoleFactoryInterface;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class RoleFactory implements RoleFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $generator)
    {
    }

    public function create(string $name, array $policies, AccountIdentifier $accountIdentifier): Role
    {
        return new Role(
            new RoleIdentifier($this->generator->generate()),
            $name,
            $policies,
            $accountIdentifier,
        );
    }
}
