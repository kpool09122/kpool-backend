<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Repository;

use Application\Http\Context\AuthContextCache;
use Application\Models\Account\Principal as PrincipalEloquent;
use Application\Models\Account\PrincipalGroup as PrincipalGroupEloquent;
use Application\Models\Account\PrincipalGroupMembership as PrincipalGroupMembershipEloquent;
use Application\Models\Account\PrincipalGroupRoleAttachment as PrincipalGroupRoleAttachmentEloquent;
use DateTimeImmutable;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\Uid\Uuid;

class PrincipalGroupRepository implements PrincipalGroupRepositoryInterface
{
    public function save(PrincipalGroup $principalGroup): void
    {
        $previousMemberIds = PrincipalGroupMembershipEloquent::query()
            ->where('principal_group_id', (string) $principalGroup->principalGroupIdentifier())
            ->pluck('principal_id')
            ->all();

        PrincipalGroupEloquent::query()->updateOrCreate(
            ['id' => (string) $principalGroup->principalGroupIdentifier()],
            [
                'account_id' => (string) $principalGroup->accountIdentifier(),
                'name' => $principalGroup->name(),
                'is_default' => $principalGroup->isDefault(),
            ]
        );

        $this->syncMembers($principalGroup);
        $this->syncRoles($principalGroup);

        $currentMemberIds = array_map(
            static fn (PrincipalIdentifier $principalIdentifier): string => (string) $principalIdentifier,
            $principalGroup->members()
        );
        $this->forgetAccountContexts(array_unique(array_merge($previousMemberIds, $currentMemberIds)));
    }

    public function findById(PrincipalGroupIdentifier $identifier): ?PrincipalGroup
    {
        $eloquent = PrincipalGroupEloquent::query()
            ->with(['members', 'roleAttachments'])
            ->where('id', (string) $identifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @return array<PrincipalGroup>
     */
    public function findByAccountId(AccountIdentifier $accountIdentifier): array
    {
        $eloquents = PrincipalGroupEloquent::query()
            ->with(['members', 'roleAttachments'])
            ->where('account_id', (string) $accountIdentifier)
            ->get();

        return $eloquents->map(fn (PrincipalGroupEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    /**
     * @return array<PrincipalGroup>
     */
    public function findByPrincipalId(PrincipalIdentifier $principalIdentifier): array
    {
        $eloquents = PrincipalGroupEloquent::query()
            ->with(['members', 'roleAttachments'])
            ->whereHas('members', function ($query) use ($principalIdentifier) {
                $query->where('principal_id', (string) $principalIdentifier);
            })
            ->get();

        return $eloquents->map(fn (PrincipalGroupEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    /**
     * @return array<PrincipalGroup>
     */
    public function findByAccountIdAndPrincipal(
        AccountIdentifier $accountIdentifier,
        PrincipalIdentifier $principalIdentifier
    ): array {
        $eloquents = PrincipalGroupEloquent::query()
            ->with(['members', 'roleAttachments'])
            ->where('account_id', (string) $accountIdentifier)
            ->whereHas('members', function ($query) use ($principalIdentifier) {
                $query->where('principal_id', (string) $principalIdentifier);
            })
            ->get();

        return $eloquents->map(fn (PrincipalGroupEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function findDefaultByAccountId(AccountIdentifier $accountIdentifier): ?PrincipalGroup
    {
        $eloquent = PrincipalGroupEloquent::query()
            ->with(['members', 'roleAttachments'])
            ->where('account_id', (string) $accountIdentifier)
            ->where('is_default', true)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByAccountIdAndRole(
        AccountIdentifier $accountIdentifier,
        RoleIdentifier $roleIdentifier
    ): ?PrincipalGroup {
        $eloquent = PrincipalGroupEloquent::query()
            ->with(['members', 'roleAttachments'])
            ->where('account_id', (string) $accountIdentifier)
            ->whereHas('roleAttachments', function ($query) use ($roleIdentifier) {
                $query->where('role_id', (string) $roleIdentifier);
            })
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function delete(PrincipalGroup $principalGroup): void
    {
        $memberIds = PrincipalGroupMembershipEloquent::query()
            ->where('principal_group_id', (string) $principalGroup->principalGroupIdentifier())
            ->pluck('principal_id')
            ->all();

        PrincipalGroupEloquent::query()
            ->where('id', (string) $principalGroup->principalGroupIdentifier())
            ->delete();

        $this->forgetAccountContexts($memberIds);
    }

    /** @param array<int, string> $principalIds */
    private function forgetAccountContexts(array $principalIds): void
    {
        if (empty($principalIds)) {
            return;
        }

        $identityIds = PrincipalEloquent::query()
            ->whereIn('id', $principalIds)
            ->pluck('identity_id')
            ->all();

        foreach ($identityIds as $identityId) {
            app(AuthContextCache::class)->forgetAccount(new IdentityIdentifier($identityId));
        }
    }

    private function syncMembers(PrincipalGroup $principalGroup): void
    {
        $principalGroupId = (string) $principalGroup->principalGroupIdentifier();

        PrincipalGroupMembershipEloquent::query()
            ->where('principal_group_id', $principalGroupId)
            ->delete();

        foreach ($principalGroup->members() as $principalIdentifier) {
            PrincipalGroupMembershipEloquent::query()->create([
                'id' => (string) Uuid::v7(),
                'principal_group_id' => $principalGroupId,
                'principal_id' => (string) $principalIdentifier,
            ]);
        }
    }

    private function syncRoles(PrincipalGroup $principalGroup): void
    {
        $principalGroupId = (string) $principalGroup->principalGroupIdentifier();

        PrincipalGroupRoleAttachmentEloquent::query()
            ->where('principal_group_id', $principalGroupId)
            ->delete();

        $records = array_map(
            static fn (RoleIdentifier $roleIdentifier) => [
                'principal_group_id' => $principalGroupId,
                'role_id' => (string) $roleIdentifier,
            ],
            $principalGroup->roles()
        );

        if (! empty($records)) {
            PrincipalGroupRoleAttachmentEloquent::query()->insert($records);
        }
    }

    private function toDomainEntity(PrincipalGroupEloquent $eloquent): PrincipalGroup
    {
        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($eloquent->id),
            new AccountIdentifier($eloquent->account_id),
            $eloquent->name,
            $eloquent->is_default,
            new DateTimeImmutable($eloquent->created_at->toDateTimeString()),
        );

        foreach ($eloquent->members as $member) {
            $principalGroup->addMember(new PrincipalIdentifier($member->principal_id));
        }

        foreach ($eloquent->roleAttachments as $roleAttachment) {
            $principalGroup->addRole(new RoleIdentifier($roleAttachment->role_id));
        }

        return $principalGroup;
    }
}
