<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Repository;

use Application\Models\Wiki\AffiliationGrant as AffiliationGrantEloquent;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Wiki\Principal\Domain\Entity\AffiliationGrant;
use Source\Wiki\Principal\Domain\Repository\AffiliationGrantRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantType;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

class AffiliationGrantRepository implements AffiliationGrantRepositoryInterface
{
    public function save(AffiliationGrant $affiliationGrant): void
    {
        AffiliationGrantEloquent::query()->updateOrCreate(
            ['id' => (string) $affiliationGrant->affiliationGrantIdentifier()],
            [
                'affiliation_id' => (string) $affiliationGrant->affiliationIdentifier(),
                'policy_id' => (string) $affiliationGrant->policyIdentifier(),
                'role_id' => (string) $affiliationGrant->roleIdentifier(),
                'principal_group_id' => (string) $affiliationGrant->principalGroupIdentifier(),
                'type' => $affiliationGrant->type()->value,
            ]
        );
    }

    public function findById(AffiliationGrantIdentifier $affiliationGrantIdentifier): ?AffiliationGrant
    {
        $eloquent = AffiliationGrantEloquent::query()
            ->where('id', (string) $affiliationGrantIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @return AffiliationGrant[]
     */
    public function findByAffiliationId(AffiliationIdentifier $affiliationIdentifier): array
    {
        $eloquentModels = AffiliationGrantEloquent::query()
            ->where('affiliation_id', (string) $affiliationIdentifier)
            ->get();

        return $eloquentModels->map(fn (AffiliationGrantEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function findByAffiliationIdAndType(
        AffiliationIdentifier $affiliationIdentifier,
        AffiliationGrantType $type,
    ): ?AffiliationGrant {
        $eloquent = AffiliationGrantEloquent::query()
            ->where('affiliation_id', (string) $affiliationIdentifier)
            ->where('type', $type->value)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function delete(AffiliationGrant $affiliationGrant): void
    {
        AffiliationGrantEloquent::query()
            ->where('id', (string) $affiliationGrant->affiliationGrantIdentifier())
            ->delete();
    }

    private function toDomainEntity(AffiliationGrantEloquent $eloquent): AffiliationGrant
    {
        return new AffiliationGrant(
            new AffiliationGrantIdentifier($eloquent->id),
            new AffiliationIdentifier($eloquent->affiliation_id),
            new PolicyIdentifier($eloquent->policy_id),
            new RoleIdentifier($eloquent->role_id),
            new PrincipalGroupIdentifier($eloquent->principal_group_id),
            AffiliationGrantType::from($eloquent->type),
            $eloquent->created_at->toDateTimeImmutable(),
        );
    }
}
