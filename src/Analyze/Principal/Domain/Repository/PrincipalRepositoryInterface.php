<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Domain\Repository;

use Source\Analyze\Principal\Domain\Entity\Principal;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface PrincipalRepositoryInterface
{
    /** @param PrincipalIdentifier[] $principalIdentifiers
     * @return array<string, Principal> */
    public function findByIds(array $principalIdentifiers): array;

    public function findById(PrincipalIdentifier $principalIdentifier): ?Principal;

    public function save(Principal $principal): void;

    public function findByIdentityIdentifierAndAccountIdentifier(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): ?Principal;

    /** @return Principal[] */
    public function findAllByIdentityIdentifier(IdentityIdentifier $identityIdentifier): array;

    public function delete(Principal $principal): void;
}
