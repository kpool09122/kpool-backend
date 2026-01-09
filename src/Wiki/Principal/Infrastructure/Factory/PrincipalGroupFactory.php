<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;

readonly class PrincipalGroupFactory implements PrincipalGroupFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        AccountIdentifier $accountIdentifier,
        string $name,
        bool $isDefault,
    ): PrincipalGroup {
        return new PrincipalGroup(
            new PrincipalGroupIdentifier($this->generator->generate()),
            $accountIdentifier,
            $name,
            $isDefault,
            new DateTimeImmutable(),
        );
    }
}
