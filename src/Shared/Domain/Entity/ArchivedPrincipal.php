<?php

declare(strict_types=1);

namespace Source\Shared\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalType;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ArchivedPrincipal
{
    public function __construct(
        private ArchivedPrincipalIdentifier $archivedPrincipalIdentifier,
        private IdentityIdentifier $identityIdentifier,
        private ArchivedPrincipalType $principalType,
        private string $principalId,
        private AccountIdentifier $accountIdentifier,
        private DateTimeImmutable $archivedAt,
    ) {
    }

    public function archivedPrincipalIdentifier(): ArchivedPrincipalIdentifier
    {
        return $this->archivedPrincipalIdentifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function principalType(): ArchivedPrincipalType
    {
        return $this->principalType;
    }

    public function principalId(): string
    {
        return $this->principalId;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function archivedAt(): DateTimeImmutable
    {
        return $this->archivedAt;
    }
}
