<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Identity\Domain\Entity\ArchivedIdentity;
use Source\Identity\Domain\Factory\ArchivedIdentityFactoryInterface;
use Source\Identity\Domain\ValueObject\ArchivedIdentityIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\SubdivisionCode;

readonly class ArchivedIdentityFactory implements ArchivedIdentityFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(
        IdentityIdentifier $identityIdentifier,
        Language $language,
        ?DateTimeImmutable $identityCreatedAt,
        ?CountryCode $registrationCountry = null,
        ?SubdivisionCode $registrationRegion = null,
    ): ArchivedIdentity {
        return new ArchivedIdentity(
            new ArchivedIdentityIdentifier($this->uuidGenerator->generate()),
            $identityIdentifier,
            $language,
            $identityCreatedAt,
            new DateTimeImmutable(),
            $registrationCountry,
            $registrationRegion,
        );
    }
}
