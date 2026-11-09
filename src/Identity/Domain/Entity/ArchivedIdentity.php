<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Identity\Domain\ValueObject\ArchivedIdentityIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\SubdivisionCode;

readonly class ArchivedIdentity
{
    public function __construct(
        private ArchivedIdentityIdentifier $archivedIdentityIdentifier,
        private IdentityIdentifier $identityIdentifier,
        private Language $language,
        private ?DateTimeImmutable $identityCreatedAt,
        private DateTimeImmutable $archivedAt,
        private ?CountryCode $registrationCountry = null,
        private ?SubdivisionCode $registrationRegion = null,
    ) {
        if ($registrationRegion !== null && $registrationRegion->country() !== $registrationCountry) {
            throw new InvalidArgumentException('Registration region must belong to registration country.');
        }
    }

    public function archivedIdentityIdentifier(): ArchivedIdentityIdentifier
    {
        return $this->archivedIdentityIdentifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function identityCreatedAt(): ?DateTimeImmutable
    {
        return $this->identityCreatedAt;
    }

    public function registrationRegion(): ?SubdivisionCode
    {
        return $this->registrationRegion;
    }

    public function registrationCountry(): ?CountryCode
    {
        return $this->registrationCountry;
    }

    public function archivedAt(): DateTimeImmutable
    {
        return $this->archivedAt;
    }
}
