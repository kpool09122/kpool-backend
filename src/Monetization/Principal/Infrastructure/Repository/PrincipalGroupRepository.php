<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Infrastructure\Repository;

use Application\Models\Monetization\Principal as PrincipalEloquent;
use Application\Models\Monetization\PrincipalGroup as PrincipalGroupEloquent;
use Application\Models\Monetization\PrincipalGroupMembership as MembershipEloquent;
use Application\Models\Monetization\PrincipalGroupRoleAttachment as AttachmentEloquent;
use Application\Models\Monetization\Role as RoleEloquent;
use InvalidArgumentException;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\PrincipalGroupDescription;

class PrincipalGroupRepository implements PrincipalGroupRepositoryInterface
{
    /** @return PrincipalGroup[] */
    public function findByAccountId(AccountIdentifier $accountIdentifier): array
    {
        return PrincipalGroupEloquent::query()
            ->with(['roleAttachments', 'memberships'])
            ->where('account_id', (string) $accountIdentifier)
            ->orderBy('created_at')->orderBy('id')
            ->get()->map(fn (PrincipalGroupEloquent $model): PrincipalGroup => $this->toDomainEntity($model))->all();
    }

    public function findById(PrincipalGroupIdentifier $principalGroupIdentifier): ?PrincipalGroup
    {
        $model = PrincipalGroupEloquent::query()
            ->with(['roleAttachments', 'memberships'])
            ->find((string) $principalGroupIdentifier);

        return $model === null ? null : $this->toDomainEntity($model);
    }

    public function findDefaultByAccountIdentifier(AccountIdentifier $accountIdentifier): ?PrincipalGroup
    {
        $model = PrincipalGroupEloquent::query()
            ->with(['roleAttachments', 'memberships'])
            ->where('account_id', (string) $accountIdentifier)->where('is_default', true)
            ->orderBy('created_at')->orderBy('id')->first();

        return $model === null ? null : $this->toDomainEntity($model);
    }

    public function findByAccountIdAndName(AccountIdentifier $accountIdentifier, string $name): ?PrincipalGroup
    {
        $model = PrincipalGroupEloquent::query()
            ->with(['roleAttachments', 'memberships'])
            ->where('account_id', (string) $accountIdentifier)->where('name', $name)->first();

        return $model === null ? null : $this->toDomainEntity($model);
    }

    public function delete(PrincipalGroup $principalGroup): void
    {
        PrincipalGroupEloquent::query()->where('id', (string) $principalGroup->principalGroupIdentifier())->delete();
    }

    public function save(PrincipalGroup $principalGroup): void
    {
        $memberIds = array_values(array_unique(array_map(static fn (PrincipalIdentifier $id): string => (string) $id, $principalGroup->members())));
        $roleIds = array_values(array_unique(array_map(static fn (RoleIdentifier $id): string => (string) $id, $principalGroup->roles())));
        $accountId = (string) $principalGroup->accountIdentifier();
        if (PrincipalEloquent::query()->whereIn('id', $memberIds)->where('account_id', $accountId)->count() !== count($memberIds)) {
            throw new InvalidArgumentException('Group members must belong to the same account.');
        }
        if (RoleEloquent::query()->whereIn('id', $roleIds)->where(function ($query) use ($accountId): void {
            $query->whereNull('account_id')->orWhere('account_id', $accountId);
        })->count() !== count($roleIds)) {
            throw new InvalidArgumentException('Group roles must be system roles or belong to the same account.');
        }

        $groupId = (string) $principalGroup->principalGroupIdentifier();
        PrincipalGroupEloquent::query()->updateOrCreate(['id' => $groupId], [
            'name' => $principalGroup->name(),
            'description' => $principalGroup->description() === null ? null : (string) $principalGroup->description(),
            'account_id' => $accountId,
            'is_default' => $principalGroup->isDefault(),
        ]);
        AttachmentEloquent::query()->where('principal_group_id', $groupId)->delete();
        foreach ($roleIds as $identifier) {
            AttachmentEloquent::query()->insert(['principal_group_id' => $groupId, 'role_id' => $identifier]);
        }
        MembershipEloquent::query()->where('principal_group_id', $groupId)->delete();
        foreach ($memberIds as $memberId) {
            MembershipEloquent::query()->insert([
                'principal_group_id' => $groupId,
                'principal_id' => $memberId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** @return PrincipalGroup[] */
    public function findByPrincipalId(PrincipalIdentifier $principalIdentifier): array
    {
        return PrincipalGroupEloquent::query()
            ->with(['roleAttachments', 'memberships'])
            ->whereHas('memberships', static function ($query) use ($principalIdentifier): void {
                $query->where('principal_id', (string) $principalIdentifier);
            })
            ->orderBy('created_at')->orderBy('id')
            ->get()->map(fn (PrincipalGroupEloquent $model): PrincipalGroup => $this->toDomainEntity($model))->all();
    }

    private function toDomainEntity(PrincipalGroupEloquent $model): PrincipalGroup
    {
        return new PrincipalGroup(
            new PrincipalGroupIdentifier($model->id),
            $model->name,
            $model->roleAttachments->map(static fn (AttachmentEloquent $attachment): RoleIdentifier => new RoleIdentifier($attachment->role_id))->all(),
            new AccountIdentifier($model->account_id),
            $model->is_default,
            $model->description === null ? null : new PrincipalGroupDescription($model->description),
            $model->memberships->map(static fn (MembershipEloquent $membership): PrincipalIdentifier => new PrincipalIdentifier($membership->principal_id))->all(),
        );
    }
}
