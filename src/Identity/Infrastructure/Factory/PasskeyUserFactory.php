<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Factory;

use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Factory\PasskeyUserFactoryInterface;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class PasskeyUserFactory implements PasskeyUserFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(?IdentityIdentifier $identityIdentifier = null): PasskeyUser
    {
        return new PasskeyUser(
            new PasskeyUserIdentifier($this->uuidGenerator->generate()),
            $identityIdentifier,
        );
    }
}
