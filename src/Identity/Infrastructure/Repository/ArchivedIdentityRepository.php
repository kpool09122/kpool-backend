<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Application\Models\Identity\ArchivedIdentity as ArchivedIdentityEloquent;
use Source\Identity\Domain\Entity\ArchivedIdentity;
use Source\Identity\Domain\Repository\ArchivedIdentityRepositoryInterface;

readonly class ArchivedIdentityRepository implements ArchivedIdentityRepositoryInterface
{
    public function save(ArchivedIdentity $archivedIdentity): void
    {
        ArchivedIdentityEloquent::query()->create([
            'id' => (string) $archivedIdentity->archivedIdentityIdentifier(),
            'identity_id' => (string) $archivedIdentity->identityIdentifier(),
            'language' => $archivedIdentity->language()->value,
            'identity_created_at' => $archivedIdentity->identityCreatedAt(),
            'archived_at' => $archivedIdentity->archivedAt(),
        ]);
    }
}
