<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Repository;

use Application\Models\Wiki\PrincipalGroup as PrincipalGroupEloquent;
use Application\Models\Wiki\PrincipalGroupMembership as PrincipalGroupMembershipEloquent;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class PrincipalGroupRepository implements PrincipalGroupRepositoryInterface
{
    public function save(PrincipalGroup $principalGroup): void
    {
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
    }

    public function findById(PrincipalGroupIdentifier $principalGroupIdentifier): ?PrincipalGroup
    {
        $eloquent = PrincipalGroupEloquent::query()
            ->with('memberships')
            ->where('id', (string) $principalGroupIdentifier)
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
        $eloquentModels = PrincipalGroupEloquent::query()
            ->with('memberships')
            ->where('account_id', (string) $accountIdentifier)
            ->get();

        return $eloquentModels->map(fn (PrincipalGroupEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    /**
     * @return array<PrincipalGroup>
     */
    public function findByPrincipalId(PrincipalIdentifier $principalIdentifier): array
    {
        $principalGroupIds = PrincipalGroupMembershipEloquent::query()
            ->where('principal_id', (string) $principalIdentifier)
            ->pluck('principal_group_id')
            ->toArray();

        if (empty($principalGroupIds)) {
            return [];
        }

        $eloquentModels = PrincipalGroupEloquent::query()
            ->with('memberships')
            ->whereIn('id', $principalGroupIds)
            ->get();

        return $eloquentModels->map(fn (PrincipalGroupEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function findDefaultByAccountId(AccountIdentifier $accountIdentifier): ?PrincipalGroup
    {
        $eloquent = PrincipalGroupEloquent::query()
            ->with('memberships')
            ->where('account_id', (string) $accountIdentifier)
            ->where('is_default', true)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function delete(PrincipalGroup $principalGroup): void
    {
        PrincipalGroupEloquent::query()
            ->where('id', (string) $principalGroup->principalGroupIdentifier())
            ->delete();
    }

    private function syncMembers(PrincipalGroup $principalGroup): void
    {
        $principalGroupId = (string) $principalGroup->principalGroupIdentifier();

        $existingMemberIds = PrincipalGroupMembershipEloquent::query()
            ->where('principal_group_id', $principalGroupId)
            ->pluck('principal_id')
            ->toArray();

        $currentMemberIds = array_map(
            static fn (PrincipalIdentifier $identifier) => (string) $identifier,
            $principalGroup->members()
        );

        $toAdd = array_diff($currentMemberIds, $existingMemberIds);
        $toRemove = array_diff($existingMemberIds, $currentMemberIds);

        if (! empty($toRemove)) {
            PrincipalGroupMembershipEloquent::query()
                ->where('principal_group_id', $principalGroupId)
                ->whereIn('principal_id', $toRemove)
                ->delete();
        }

        if (! empty($toAdd)) {
            $records = array_map(
                static fn (string $principalId) => [
                    'principal_group_id' => $principalGroupId,
                    'principal_id' => $principalId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                $toAdd
            );
            PrincipalGroupMembershipEloquent::query()->insert($records);
        }
    }

    private function syncRoles(PrincipalGroup $principalGroup): void
    {
        $principalGroupId = (string) $principalGroup->principalGroupIdentifier();

        // 現在のアタッチメントを削除
        DB::table('principal_group_role_attachments')
            ->where('principal_group_id', $principalGroupId)
            ->delete();

        // 新しいアタッチメントを挿入
        foreach ($principalGroup->roles() as $roleIdentifier) {
            DB::table('principal_group_role_attachments')->insert([
                'principal_group_id' => $principalGroupId,
                'role_id' => (string) $roleIdentifier,
            ]);
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

        foreach ($eloquent->memberships as $membership) {
            $principalGroup->addMember(new PrincipalIdentifier($membership->principal_id));
        }

        // Load roles
        $roleIds = DB::table('principal_group_role_attachments')
            ->where('principal_group_id', $eloquent->id)
            ->pluck('role_id')
            ->toArray();

        foreach ($roleIds as $roleId) {
            $principalGroup->addRole(new RoleIdentifier($roleId));
        }

        return $principalGroup;
    }
}
