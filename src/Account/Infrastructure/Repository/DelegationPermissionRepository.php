<?php

declare(strict_types=1);

namespace Source\Account\Infrastructure\Repository;

use Application\Models\Account\DelegationPermission as DelegationPermissionEloquent;
use DateTimeImmutable;
use Source\Account\Domain\Entity\DelegationPermission;
use Source\Account\Domain\Repository\DelegationPermissionRepositoryInterface;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class DelegationPermissionRepository implements DelegationPermissionRepositoryInterface
{
    public function save(DelegationPermission $delegationPermission): void
    {
        DelegationPermissionEloquent::query()->updateOrCreate(
            ['id' => (string) $delegationPermission->delegationPermissionIdentifier()],
            [
                'identity_group_id' => (string) $delegationPermission->identityGroupIdentifier(),
                'target_account_id' => (string) $delegationPermission->targetAccountIdentifier(),
                'affiliation_id' => (string) $delegationPermission->affiliationIdentifier(),
            ]
        );
    }

    public function findById(DelegationPermissionIdentifier $identifier): ?DelegationPermission
    {
        $eloquent = DelegationPermissionEloquent::query()
            ->where('id', (string) $identifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByAffiliationId(AffiliationIdentifier $affiliationIdentifier): ?DelegationPermission
    {
        $eloquent = DelegationPermissionEloquent::query()
            ->where('affiliation_id', (string) $affiliationIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @param array<IdentityGroupIdentifier> $identityGroupIdentifiers
     */
    public function existsForAnyIdentityGroup(array $identityGroupIdentifiers, AccountIdentifier $targetAccountIdentifier): bool
    {
        $identityGroupIds = array_map(fn ($id) => (string) $id, $identityGroupIdentifiers);

        return DelegationPermissionEloquent::query()
            ->whereIn('identity_group_id', $identityGroupIds)
            ->where('target_account_id', (string) $targetAccountIdentifier)
            ->exists();
    }

    public function delete(DelegationPermission $delegationPermission): void
    {
        DelegationPermissionEloquent::query()
            ->where('id', (string) $delegationPermission->delegationPermissionIdentifier())
            ->delete();
    }

    private function toDomainEntity(DelegationPermissionEloquent $eloquent): DelegationPermission
    {
        return new DelegationPermission(
            new DelegationPermissionIdentifier($eloquent->id),
            new IdentityGroupIdentifier($eloquent->identity_group_id),
            new AccountIdentifier($eloquent->target_account_id),
            new AffiliationIdentifier($eloquent->affiliation_id),
            new DateTimeImmutable($eloquent->created_at->toDateTimeString()),
        );
    }
}
