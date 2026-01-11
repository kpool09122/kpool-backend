<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Repository;

use Application\Models\Wiki\Principal as PrincipalEloquent;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

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

    public function findByDelegation(DelegationIdentifier $delegationIdentifier): ?Principal
    {
        $eloquent = PrincipalEloquent::query()
            ->with('groups')
            ->where('delegation_identifier', (string) $delegationIdentifier)
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
                'agency_id' => $principal->agencyId(),
                'talent_ids' => $principal->talentIds(),
                'delegation_identifier' => $principal->delegationIdentifier() !== null
                    ? (string) $principal->delegationIdentifier()
                    : null,
                'enabled' => $principal->isEnabled(),
            ]
        );

        $eloquent->groups()->sync($principal->groupIds());
    }

    public function deleteByDelegation(DelegationIdentifier $delegationIdentifier): void
    {
        PrincipalEloquent::query()
            ->where('delegation_identifier', (string) $delegationIdentifier)
            ->delete();
    }

    private function toDomainEntity(PrincipalEloquent $eloquent): Principal
    {
        return new Principal(
            new PrincipalIdentifier($eloquent->id),
            new IdentityIdentifier($eloquent->identity_id),
            $eloquent->agency_id,
            $eloquent->groups->pluck('id')->all(),
            $eloquent->talent_ids,
            $eloquent->delegation_identifier !== null
                ? new DelegationIdentifier($eloquent->delegation_identifier)
                : null,
            $eloquent->enabled,
        );
    }
}
