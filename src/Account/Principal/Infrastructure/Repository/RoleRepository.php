<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Repository;

use Application\Models\Account\RolePolicyAttachment as RolePolicyAttachmentEloquent;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;

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
}
