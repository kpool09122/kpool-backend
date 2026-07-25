<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Repository;

use Application\Http\Context\AuthContextCache;
use Application\Models\Account\Principal as PrincipalEloquent;
use Application\Models\Account\PrincipalGroup as PrincipalGroupEloquent;
use Application\Models\Account\RolePolicyAttachment as RolePolicyAttachmentEloquent;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class RoleRepository implements RoleRepositoryInterface
{
    public function save(Role $role): void
    {
        $roleValue = $role->role()->value;

        RolePolicyAttachmentEloquent::query()
            ->where('role', $roleValue)
            ->delete();

        $records = array_map(
            static fn (PolicyIdentifier $policyIdentifier) => [
                'role' => $roleValue,
                'policy_id' => (string) $policyIdentifier,
            ],
            $role->policies()
        );

        if (! empty($records)) {
            RolePolicyAttachmentEloquent::query()->insert($records);
        }

        $this->forgetAccountContextsForRoles([$role->role()]);
    }

    public function findByRole(AccountRole $role): Role
    {
        $roles = $this->findByRoles([$role]);

        return $roles[$role->value] ?? new Role($role, []);
    }

    /**
     * @param AccountRole[] $roles
     * @return array<string, Role>
     */
    public function findByRoles(array $roles): array
    {
        if (empty($roles)) {
            return [];
        }

        $roleValues = array_map(static fn (AccountRole $role) => $role->value, $roles);

        $attachments = RolePolicyAttachmentEloquent::query()
            ->whereIn('role', $roleValues)
            ->get()
            ->groupBy('role');

        $result = [];
        foreach ($roles as $role) {
            $policies = ($attachments[$role->value] ?? collect())
                ->map(static fn (RolePolicyAttachmentEloquent $attachment) => new PolicyIdentifier($attachment->policy_id))
                ->all();

            $result[$role->value] = new Role($role, $policies);
        }

        return $result;
    }

    /** @param AccountRole[] $roles */
    private function forgetAccountContextsForRoles(array $roles): void
    {
        if (empty($roles)) {
            return;
        }

        $roleValues = array_map(static fn (AccountRole $role): string => $role->value, $roles);
        $principalIds = PrincipalGroupEloquent::query()
            ->whereIn('role', $roleValues)
            ->join('account_principal_group_memberships', 'account_principal_groups.id', '=', 'account_principal_group_memberships.principal_group_id')
            ->pluck('account_principal_group_memberships.principal_id')
            ->unique()
            ->values()
            ->all();

        $identityIds = PrincipalEloquent::query()
            ->whereIn('id', $principalIds)
            ->pluck('identity_id')
            ->all();

        foreach ($identityIds as $identityId) {
            app(AuthContextCache::class)->forgetAccount(new IdentityIdentifier($identityId));
        }
    }
}
