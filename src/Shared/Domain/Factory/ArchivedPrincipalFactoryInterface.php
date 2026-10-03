<?php

declare(strict_types=1);

namespace Source\Shared\Domain\Factory;

use Source\Shared\Domain\Entity\ArchivedPrincipal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalType;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ArchivedPrincipalFactoryInterface
{
    public function create(
        IdentityIdentifier $identityIdentifier,
        ArchivedPrincipalType $principalType,
        string $principalId,
        AccountIdentifier $accountIdentifier,
    ): ArchivedPrincipal;
}
