<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Repository;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface PrincipalRepositoryInterface
{
    public function findById(PrincipalIdentifier $principalIdentifier): ?Principal;

    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): ?Principal;

    public function findByIdentityIdentifierAndAccountIdentifier(
        IdentityIdentifier $identityIdentifier,
        AccountIdentifier $accountIdentifier,
    ): ?Principal;

    public function save(Principal $principal): void;
}
