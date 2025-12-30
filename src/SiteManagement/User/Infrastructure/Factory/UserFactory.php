<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Infrastructure\Factory;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Domain\Entity\User;
use Source\SiteManagement\User\Domain\Factory\UserFactoryInterface;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;
use Symfony\Component\Uid\Ulid;

readonly class UserFactory implements UserFactoryInterface
{
    public function create(IdentityIdentifier $identityIdentifier): User
    {
        return new User(
            new UserIdentifier(Ulid::generate()),
            $identityIdentifier,
            Role::NONE,
        );
    }
}
