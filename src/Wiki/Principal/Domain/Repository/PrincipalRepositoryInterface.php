<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Repository;

use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface PrincipalRepositoryInterface
{
    public function findById(PrincipalIdentifier $principalIdentifier): ?Principal;

    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): ?Principal;

    public function findByDelegation(DelegationIdentifier $delegationIdentifier): ?Principal;

    public function save(Principal $principal): void;

    public function deleteByDelegation(DelegationIdentifier $delegationIdentifier): void;
}
