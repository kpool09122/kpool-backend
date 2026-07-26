<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Repository;

use Application\Http\Context\AuthContextCache;
use Application\Models\Account\Principal as PrincipalEloquent;
use Application\Models\Account\PrincipalGroupMembership as PrincipalGroupMembershipEloquent;
use Application\Models\Account\PrincipalGroupRoleAttachment as PrincipalGroupRoleAttachmentEloquent;
use Application\Models\Account\Role as RoleEloquent;
use Application\Models\Account\RolePolicyAttachment as RolePolicyAttachmentEloquent;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

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
        $this->forgetAccountContextsForRole((string) $role->roleIdentifier());
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

        $ids = array_map(static fn (RoleIdentifier $roleIdentifier): string => (string) $roleIdentifier, $roleIdentifiers);

        $eloquents = RoleEloquent::query()
            ->with('policyAttachments')
            ->whereIn('id', $ids)
            ->get();

        $result = [];
        foreach ($eloquents as $eloquent) {
            $result[$eloquent->id] = $this->toDomainEntity($eloquent);
        }

        return $result;
    }

    public function findByName(string $name): ?Role
    {
        $eloquent = RoleEloquent::query()
            ->with('policyAttachments')
            ->where('name', $name)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
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

    private function forgetAccountContextsForRole(string $roleId): void
    {
        $principalGroupIds = PrincipalGroupRoleAttachmentEloquent::query()
            ->where('role_id', $roleId)
            ->pluck('principal_group_id')
            ->all();

        if (empty($principalGroupIds)) {
            return;
        }

        $principalIds = PrincipalGroupMembershipEloquent::query()
            ->whereIn('principal_group_id', $principalGroupIds)
            ->pluck('principal_id')
            ->unique()
            ->values()
            ->all();

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

    private function toDomainEntity(RoleEloquent $eloquent): Role
    {
        $policies = $eloquent->policyAttachments->map(
            static fn (RolePolicyAttachmentEloquent $attachment) => new PolicyIdentifier($attachment->policy_id)
        )->all();

        return new Role(
            new RoleIdentifier($eloquent->id),
            $eloquent->name,
            $policies,
            $eloquent->is_system_role,
        );
    }
}
