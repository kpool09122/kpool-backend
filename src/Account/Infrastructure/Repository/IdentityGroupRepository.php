<?php

declare(strict_types=1);

namespace Source\Account\Infrastructure\Repository;

use Application\Models\Account\IdentityGroup as IdentityGroupEloquent;
use Application\Models\Account\IdentityGroupMembership;
use Application\Models\Account\IdentityGroupMembership as IdentityGroupMembershipEloquent;
use DateTimeImmutable;
use Source\Account\Domain\Entity\IdentityGroup;
use Source\Account\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\Domain\ValueObject\AccountRole;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\Uid\Uuid;

class IdentityGroupRepository implements IdentityGroupRepositoryInterface
{
    public function save(IdentityGroup $identityGroup): void
    {
        IdentityGroupEloquent::query()->updateOrCreate(
            ['id' => (string) $identityGroup->identityGroupIdentifier()],
            [
                'account_id' => (string) $identityGroup->accountIdentifier(),
                'name' => $identityGroup->name(),
                'role' => $identityGroup->role()->value,
                'is_default' => $identityGroup->isDefault(),
            ]
        );

        IdentityGroupMembershipEloquent::query()
            ->where('identity_group_id', (string) $identityGroup->identityGroupIdentifier())
            ->delete();

        foreach ($identityGroup->members() as $identityIdentifier) {
            IdentityGroupMembershipEloquent::query()->create([
                'id' => (string) Uuid::v7(),
                'identity_group_id' => (string) $identityGroup->identityGroupIdentifier(),
                'identity_id' => (string) $identityIdentifier,
            ]);
        }
    }

    public function findById(IdentityGroupIdentifier $identifier): ?IdentityGroup
    {
        $eloquent = IdentityGroupEloquent::query()
            ->with('members')
            ->where('id', (string) $identifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @return array<IdentityGroup>
     */
    public function findByAccountId(AccountIdentifier $accountIdentifier): array
    {
        $eloquents = IdentityGroupEloquent::query()
            ->with('members')
            ->where('account_id', (string) $accountIdentifier)
            ->get();

        return $eloquents->map(fn ($e) => $this->toDomainEntity($e))->all();
    }

    /**
     * @return array<IdentityGroup>
     */
    public function findByIdentityId(IdentityIdentifier $identityIdentifier): array
    {
        $eloquents = IdentityGroupEloquent::query()
            ->with('members')
            ->whereHas('members', function ($query) use ($identityIdentifier) {
                $query->where('identity_id', (string) $identityIdentifier);
            })
            ->get();

        return $eloquents->map(fn ($e) => $this->toDomainEntity($e))->all();
    }

    public function findDefaultByAccountId(AccountIdentifier $accountIdentifier): ?IdentityGroup
    {
        $eloquent = IdentityGroupEloquent::query()
            ->with('members')
            ->where('account_id', (string) $accountIdentifier)
            ->where('is_default', true)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function delete(IdentityGroup $identityGroup): void
    {
        IdentityGroupEloquent::query()
            ->where('id', (string) $identityGroup->identityGroupIdentifier())
            ->delete();
    }

    private function toDomainEntity(IdentityGroupEloquent $eloquent): IdentityGroup
    {
        $identityGroup = new IdentityGroup(
            new IdentityGroupIdentifier($eloquent->id),
            new AccountIdentifier($eloquent->account_id),
            $eloquent->name,
            AccountRole::from($eloquent->role),
            $eloquent->is_default,
            new DateTimeImmutable($eloquent->created_at->toDateTimeString()),
        );

        /** @var IdentityGroupMembership $member */
        foreach ($eloquent->members as $member) {
            $identityGroup->addMember(new IdentityIdentifier($member->identity_id));
        }

        return $identityGroup;
    }
}
