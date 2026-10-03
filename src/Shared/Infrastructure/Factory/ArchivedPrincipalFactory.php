<?php

declare(strict_types=1);

namespace Source\Shared\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\Entity\ArchivedPrincipal;
use Source\Shared\Domain\Factory\ArchivedPrincipalFactoryInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalType;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ArchivedPrincipalFactory implements ArchivedPrincipalFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(
        IdentityIdentifier $identityIdentifier,
        ArchivedPrincipalType $principalType,
        string $principalId,
        AccountIdentifier $accountIdentifier,
    ): ArchivedPrincipal {
        return new ArchivedPrincipal(
            new ArchivedPrincipalIdentifier($this->uuidGenerator->generate()),
            $identityIdentifier,
            $principalType,
            $principalId,
            $accountIdentifier,
            new DateTimeImmutable(),
        );
    }
}
