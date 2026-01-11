<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Account\IdentityGroup\Domain\Factory\IdentityGroupFactoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class IdentityGroupFactory implements IdentityGroupFactoryInterface
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
    ): IdentityGroup {
        return new IdentityGroup(
            new IdentityGroupIdentifier($this->generator->generate()),
            $accountIdentifier,
            $name,
            $role,
            $isDefault,
            new DateTimeImmutable(),
        );
    }
}
