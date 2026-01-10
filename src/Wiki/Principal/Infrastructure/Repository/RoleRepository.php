<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Repository;

use Application\Models\Wiki\Role as RoleEloquent;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

class RoleRepository implements RoleRepositoryInterface
{
    public function save(Role $role): void
    {
        RoleEloquent::query()->updateOrCreate(
            ['id' => (string) $role->roleIdentifier()],
            [
                'name' => $role->name(),
                'is_system_role' => $role->isSystemRole(),
            ]
        );

        // Sync role_policy_attachments
        $this->syncPolicies($role);
    }

    public function findById(RoleIdentifier $roleIdentifier): ?Role
    {
        $eloquent = RoleEloquent::query()
            ->where('id', (string) $roleIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @param RoleIdentifier[] $roleIdentifiers
     * @return array<string, Role>
     */
    public function findByIds(array $roleIdentifiers): array
    {
        if (empty($roleIdentifiers)) {
            return [];
        }

        $ids = array_map(fn (RoleIdentifier $id) => (string) $id, $roleIdentifiers);

        // Role と PolicyAttachments を一括取得
        $eloquentModels = RoleEloquent::query()
            ->whereIn('id', $ids)
            ->get();

        // PolicyAttachments を一括取得
        $policyAttachments = DB::table('role_policy_attachments')
            ->whereIn('role_id', $ids)
            ->get()
            ->groupBy('role_id');

        $result = [];
        foreach ($eloquentModels as $eloquent) {
            $roleId = $eloquent->id;
            $policyIds = $policyAttachments->get($roleId, collect())->pluck('policy_id')->toArray();
            $policies = array_map(
                fn (string $policyId) => new PolicyIdentifier($policyId),
                $policyIds
            );

            $result[$roleId] = new Role(
                new RoleIdentifier($roleId),
                $eloquent->name,
                $policies,
                $eloquent->is_system_role,
                new DateTimeImmutable($eloquent->created_at->toDateTimeString()),
            );
        }

        return $result;
    }

    /**
     * @return array<Role>
     */
    public function findAll(): array
    {
        $eloquentModels = RoleEloquent::query()->get();

        return $eloquentModels->map(fn (RoleEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function delete(Role $role): void
    {
        // 先にアタッチメントを削除
        DB::table('role_policy_attachments')
            ->where('role_id', (string) $role->roleIdentifier())
            ->delete();

        RoleEloquent::query()
            ->where('id', (string) $role->roleIdentifier())
            ->delete();
    }

    private function syncPolicies(Role $role): void
    {
        $roleId = (string) $role->roleIdentifier();

        // 現在のアタッチメントを削除
        DB::table('role_policy_attachments')
            ->where('role_id', $roleId)
            ->delete();

        // 新しいアタッチメントを挿入
        foreach ($role->policies() as $policyIdentifier) {
            DB::table('role_policy_attachments')->insert([
                'role_id' => $roleId,
                'policy_id' => (string) $policyIdentifier,
            ]);
        }
    }

    private function toDomainEntity(RoleEloquent $eloquent): Role
    {
        $policyIds = DB::table('role_policy_attachments')
            ->where('role_id', $eloquent->id)
            ->pluck('policy_id')
            ->toArray();

        $policies = array_map(
            fn (string $policyId) => new PolicyIdentifier($policyId),
            $policyIds
        );

        return new Role(
            new RoleIdentifier($eloquent->id),
            $eloquent->name,
            $policies,
            $eloquent->is_system_role,
            new DateTimeImmutable($eloquent->created_at->toDateTimeString()),
        );
    }
}
