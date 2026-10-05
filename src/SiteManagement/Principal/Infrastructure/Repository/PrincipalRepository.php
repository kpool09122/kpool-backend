<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Infrastructure\Repository;

use Application\Models\SiteManagement\Principal as PrincipalEloquent;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

class PrincipalRepository implements PrincipalRepositoryInterface
{
    public function save(Principal $principal): void
    {
        PrincipalEloquent::query()->updateOrCreate(['id' => (string) $principal->principalIdentifier()], ['identity_id' => (string) $principal->identityIdentifier()]);
    }

    public function findById(PrincipalIdentifier $principalIdentifier): ?Principal
    {
        $model = PrincipalEloquent::query()->find((string) $principalIdentifier);

        return $model === null ? null : new Principal(new PrincipalIdentifier($model->id), new IdentityIdentifier($model->identity_id));
    }

    public function findByIdentityId(IdentityIdentifier $identityIdentifier): ?Principal
    {
        $model = PrincipalEloquent::query()->where('identity_id', (string) $identityIdentifier)->first();

        return $model === null ? null : new Principal(new PrincipalIdentifier($model->id), new IdentityIdentifier($model->identity_id));
    }

    public function delete(Principal $principal): void
    {
        PrincipalEloquent::query()->where('id', (string) $principal->principalIdentifier())->delete();
    }
}
