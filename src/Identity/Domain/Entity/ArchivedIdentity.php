<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Entity;

use DateTimeImmutable;
use Source\Identity\Domain\ValueObject\ArchivedIdentityIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;

readonly class ArchivedIdentity
{
    public function __construct(
        private ArchivedIdentityIdentifier $archivedIdentityIdentifier,
        private IdentityIdentifier $identityIdentifier,
        private Language $language,
        private ?DateTimeImmutable $identityCreatedAt,
        private DateTimeImmutable $archivedAt,
    ) {
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

    public function archivedAt(): DateTimeImmutable
    {
        return $this->archivedAt;
    }
}
