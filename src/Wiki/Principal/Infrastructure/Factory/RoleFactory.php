<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Factory\RoleFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

readonly class RoleFactory implements RoleFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function create(
        string $name,
        array $policies,
        bool $isSystemRole,
    ): Role {
        return new Role(
            new RoleIdentifier($this->generator->generate()),
            $name,
            $policies,
            $isSystemRole,
            new DateTimeImmutable(),
        );
    }
}
