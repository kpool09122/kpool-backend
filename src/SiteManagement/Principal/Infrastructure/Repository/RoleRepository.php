<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Infrastructure\Repository;

use Application\Models\SiteManagement\Role as RoleEloquent;
use Application\Models\SiteManagement\RolePolicyAttachment as AttachmentEloquent;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Role;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

class RoleRepository implements RoleRepositoryInterface
{
    public function save(Role $role): void
    {
        RoleEloquent::query()->updateOrCreate(['id' => (string) $role->roleIdentifier()], ['account_id' => $role->accountIdentifier() === null ? null : (string) $role->accountIdentifier(), 'name' => $role->name()]);
        AttachmentEloquent::query()->where('role_id', (string) $role->roleIdentifier())->delete();
        foreach ($role->policies() as $identifier) {
            AttachmentEloquent::query()->insert(['role_id' => (string) $role->roleIdentifier(), 'policy_id' => (string) $identifier]);
        }
    }

    /** @param RoleIdentifier[] $identifiers
     * @return Role[] */
    public function findByIds(array $identifiers): array
    {
        if ($identifiers === []) {
            return [];
        } $models = RoleEloquent::query()->whereIn("id", array_map(static fn (RoleIdentifier $id): string => (string) $id, $identifiers))->get();
        $attachments = AttachmentEloquent::query()->whereIn('role_id', $models->pluck('id'))->get();
        $result = [];
        foreach ($models as $model) {
            $entity = new Role(new RoleIdentifier($model->id), $model->name, $attachments->where('role_id', $model->id)->map(static fn (AttachmentEloquent $attachment): PolicyIdentifier => new PolicyIdentifier($attachment->policy_id))->all(), $model->account_id === null ? null : new AccountIdentifier($model->account_id));
            $result[] = $entity;
        }

        return $result;
    }
}
