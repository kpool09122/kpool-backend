<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Domain\Entity\User;
use Source\SiteManagement\User\Domain\Factory\UserFactoryInterface;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

readonly class UserFactory implements UserFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(IdentityIdentifier $identityIdentifier): User
    {
        return new User(
            new UserIdentifier($this->uuidGenerator->generate()),
            $identityIdentifier,
            Role::NONE,
        );
    }
}
