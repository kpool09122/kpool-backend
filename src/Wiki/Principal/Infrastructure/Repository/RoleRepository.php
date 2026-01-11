<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Repository;

use Application\Models\Wiki\Role as RoleEloquent;
use Application\Models\Wiki\RolePolicyAttachment as RolePolicyAttachmentEloquent;
use DateTimeImmutable;
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

        $this->syncPolicies($role);
    }

    public function findById(RoleIdentifier $roleIdentifier): ?Role
    {
        $eloquent = RoleEloquent::query()
            ->with('policyAttachments')
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

        $ids = array_map(static fn (RoleIdentifier $id) => (string) $id, $roleIdentifiers);

        $eloquentModels = RoleEloquent::query()
            ->with('policyAttachments')
            ->whereIn('id', $ids)
            ->get();

        $result = [];
        foreach ($eloquentModels as $eloquent) {
            $result[$eloquent->id] = $this->toDomainEntity($eloquent);
        }

        return $result;
    }

    /**
     * @return array<Role>
     */
    public function findAll(): array
    {
        $eloquentModels = RoleEloquent::query()
            ->with('policyAttachments')
            ->get();

        return $eloquentModels->map(fn (RoleEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function delete(Role $role): void
    {
        RoleEloquent::query()
            ->where('id', (string) $role->roleIdentifier())
            ->delete();
    }

    private function syncPolicies(Role $role): void
    {
        $roleId = (string) $role->roleIdentifier();

        RolePolicyAttachmentEloquent::query()
            ->where('role_id', $roleId)
            ->delete();

        $records = array_map(
            static fn (PolicyIdentifier $policyIdentifier) => [
                'role_id' => $roleId,
                'policy_id' => (string) $policyIdentifier,
            ],
            $role->policies()
        );

        if (! empty($records)) {
            RolePolicyAttachmentEloquent::query()->insert($records);
        }
    }

    private function toDomainEntity(RoleEloquent $eloquent): Role
    {
        $policies = $eloquent->policyAttachments->map(
            fn (RolePolicyAttachmentEloquent $attachment) => new PolicyIdentifier($attachment->policy_id)
        )->all();

        return new Role(
            new RoleIdentifier($eloquent->id),
            $eloquent->name,
            $policies,
            $eloquent->is_system_role,
            new DateTimeImmutable($eloquent->created_at->toDateTimeString()),
        );
    }
}
