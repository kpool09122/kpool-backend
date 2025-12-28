<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Infrastructure\Repository;

use Application\Models\Wiki\Principal as PrincipalEloquent;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\AccessControl\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;

class PrincipalRepository implements PrincipalRepositoryInterface
{
    public function findById(PrincipalIdentifier $principalIdentifier): ?Principal
    {
        $eloquent = PrincipalEloquent::query()
            ->with('groups')
            ->where('id', (string) $principalIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): ?Principal
    {
        $eloquent = PrincipalEloquent::query()
            ->with('groups')
            ->where('identity_id', (string) $identityIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function save(Principal $principal): void
    {
        $eloquent = PrincipalEloquent::query()->updateOrCreate(
            ['id' => (string) $principal->principalIdentifier()],
            [
                'identity_id' => (string) $principal->identityIdentifier(),
                'role' => $principal->role()->value,
                'agency_id' => $principal->agencyId(),
                'talent_ids' => $principal->talentIds(),
            ]
        );

        $eloquent->groups()->sync($principal->groupIds());
    }

    private function toDomainEntity(PrincipalEloquent $eloquent): Principal
    {
        return new Principal(
            new PrincipalIdentifier($eloquent->id),
            new IdentityIdentifier($eloquent->identity_id),
            Role::from($eloquent->role),
            $eloquent->agency_id,
            $eloquent->groups->pluck('id')->all(),
            $eloquent->talent_ids,
        );
    }
}
