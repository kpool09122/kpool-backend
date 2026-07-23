<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class PrincipalGroupFactory implements PrincipalGroupFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        AccountIdentifier $accountIdentifier,
        string $name,
        AccountRole $role,
        bool $isDefault,
    ): PrincipalGroup {
        return new PrincipalGroup(
            new PrincipalGroupIdentifier($this->generator->generate()),
            $accountIdentifier,
            $name,
            $role,
            $isDefault,
            new DateTimeImmutable(),
        );
    }
}
