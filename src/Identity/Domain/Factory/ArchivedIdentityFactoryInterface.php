<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Factory;

use DateTimeImmutable;
use Source\Identity\Domain\Entity\ArchivedIdentity;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;

interface ArchivedIdentityFactoryInterface
{
    public function create(
        IdentityIdentifier $identityIdentifier,
        Language $language,
        ?DateTimeImmutable $identityCreatedAt,
    ): ArchivedIdentity;
}
