<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Repository;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface PrincipalRepositoryInterface
{
    public function findById(PrincipalIdentifier $principalIdentifier): ?Principal;

    public function save(Principal $principal): void;

    public function findByIdentityIdentifierAndAccountIdentifier(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): ?Principal;

    /** @return Principal[] */
    public function findAllByIdentityIdentifier(IdentityIdentifier $identityIdentifier): array;

    public function delete(Principal $principal): void;
}
