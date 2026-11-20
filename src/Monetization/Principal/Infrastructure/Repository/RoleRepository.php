<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Infrastructure\Repository;

use Application\Models\Monetization\Policy as PolicyEloquent;
use Application\Models\Monetization\Role as RoleEloquent;
use Application\Models\Monetization\RolePolicyAttachment as AttachmentEloquent;
use InvalidArgumentException;
use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class RoleRepository implements RoleRepositoryInterface
{
    public function save(Role $role): void
    {
        $policyIds = array_values(array_unique(array_map(static fn (PolicyIdentifier $id): string => (string) $id, $role->policies())));
        $query = PolicyEloquent::query()->whereIn('id', $policyIds);
        if ($role->accountIdentifier() === null) {
            $query->whereNull('account_id');
        } else {
            $query->where(function ($query) use ($role): void {
                $query->whereNull('account_id')->orWhere('account_id', (string) $role->accountIdentifier());
            });
        }
        if ($query->count() !== count($policyIds)) {
            throw new InvalidArgumentException('Role and policy account scopes are incompatible.');
        }

        RoleEloquent::query()->updateOrCreate(['id' => (string) $role->roleIdentifier()], ['account_id' => $role->accountIdentifier() === null ? null : (string) $role->accountIdentifier(), 'name' => $role->name()]);
        AttachmentEloquent::query()->where('role_id', (string) $role->roleIdentifier())->delete();
        foreach ($policyIds as $identifier) {
            AttachmentEloquent::query()->insert(['role_id' => (string) $role->roleIdentifier(), 'policy_id' => (string) $identifier]);
        }
    }

    public function findSystemByName(string $name): ?Role
    {
        $model = RoleEloquent::query()
            ->with('policyAttachments')
            ->whereNull('account_id')
            ->where('name', $name)
            ->first();

        return $model === null ? null : $this->toDomainEntity($model);
    }

    /** @param RoleIdentifier[] $identifiers
     * @return Role[] */
    public function findByIds(array $identifiers): array
    {
        if ($identifiers === []) {
            return [];
        }

        return RoleEloquent::query()
            ->with('policyAttachments')
            ->whereIn('id', array_map(static fn (RoleIdentifier $id): string => (string) $id, $identifiers))
            ->get()
            ->map(fn (RoleEloquent $model): Role => $this->toDomainEntity($model))
            ->all();
    }

    private function toDomainEntity(RoleEloquent $model): Role
    {
        return new Role(
            new RoleIdentifier($model->id),
            $model->name,
            $model->policyAttachments
                ->map(static fn (AttachmentEloquent $attachment): PolicyIdentifier => new PolicyIdentifier($attachment->policy_id))
                ->all(),
            $model->account_id === null ? null : new AccountIdentifier($model->account_id),
        );
    }
}
