<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Repository;

use Application\Http\Context\AuthContextCache;
use Application\Models\Wiki\Principal as PrincipalEloquent;
use Application\Models\Wiki\PrincipalGroupMembership as PrincipalGroupMembershipEloquent;
use Application\Models\Wiki\PrincipalGroupRoleAttachment as PrincipalGroupRoleAttachmentEloquent;
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
        $this->forgetWikiContextsForRole((string) $role->roleIdentifier());
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

    public function delete(Role $role): void
    {
        $this->forgetWikiContextsForRole((string) $role->roleIdentifier());

        RoleEloquent::query()
            ->where('id', (string) $role->roleIdentifier())
            ->delete();
    }

    private function forgetWikiContextsForRole(string $roleId): void
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
            ->all();

        if (empty($principalIds)) {
            return;
        }

        $identityIds = PrincipalEloquent::query()
            ->whereIn('id', $principalIds)
            ->pluck('identity_id')
            ->all();

        foreach ($identityIds as $identityId) {
            app(AuthContextCache::class)->forgetWiki(new \Source\Shared\Domain\ValueObject\IdentityIdentifier($identityId));
        }
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
