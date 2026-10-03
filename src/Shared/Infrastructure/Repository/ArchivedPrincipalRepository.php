<?php

declare(strict_types=1);

namespace Source\Shared\Infrastructure\Repository;

use Application\Models\Shared\ArchivedPrincipal as ArchivedPrincipalEloquent;
use Source\Shared\Domain\Entity\ArchivedPrincipal;
use Source\Shared\Domain\Repository\ArchivedPrincipalRepositoryInterface;

readonly class ArchivedPrincipalRepository implements ArchivedPrincipalRepositoryInterface
{
    public function save(ArchivedPrincipal $archivedPrincipal): void
    {
        ArchivedPrincipalEloquent::query()->create([
            'id' => (string) $archivedPrincipal->archivedPrincipalIdentifier(),
            'identity_id' => (string) $archivedPrincipal->identityIdentifier(),
            'principal_type' => $archivedPrincipal->principalType()->value,
            'principal_id' => $archivedPrincipal->principalId(),
            'account_id' => (string) $archivedPrincipal->accountIdentifier(),
            'archived_at' => $archivedPrincipal->archivedAt(),
        ]);
    }
}
