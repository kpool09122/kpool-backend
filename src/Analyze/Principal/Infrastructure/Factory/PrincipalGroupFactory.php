<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Infrastructure\Factory;

use Source\Analyze\Principal\Domain\Entity\PrincipalGroup;
use Source\Analyze\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class PrincipalGroupFactory implements PrincipalGroupFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    /** @param RoleIdentifier[] $roles */
    public function create(string $name, array $roles, AccountIdentifier $accountIdentifier, bool $isDefault = false): PrincipalGroup
    {
        return new PrincipalGroup(new PrincipalGroupIdentifier($this->uuidGenerator->generate()), $name, $roles, $accountIdentifier, $isDefault, null, []);
    }
}
