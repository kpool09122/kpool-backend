<?php

declare(strict_types=1);

namespace Source\Auth\Infrastructure\Factory;

use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Factory\UserFactoryInterface;
use Source\Auth\Domain\ValueObject\HashedPassword;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\UserIdentifier;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Translation;

readonly class UserFactory implements UserFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        UserName $username,
        Email $email,
        Translation $translation,
        PlainPassword $plainPassword,
    ): User {
        return new User(
            new UserIdentifier($this->ulidGenerator->generate()),
            $username,
            $email,
            $translation,
            null,
            HashedPassword::fromPlain($plainPassword),
            [],
            null,
        );
    }
}
