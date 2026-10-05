<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Repository;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface PrincipalRepositoryInterface
{
    public function findById(PrincipalIdentifier $principalIdentifier): ?Principal;

    public function save(Principal $principal): void;

    public function findByIdentityId(IdentityIdentifier $identityIdentifier): ?Principal;

    public function delete(Principal $principal): void;
}
