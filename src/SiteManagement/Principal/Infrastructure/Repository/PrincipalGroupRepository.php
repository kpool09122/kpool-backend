<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Infrastructure\Repository;

use Application\Models\SiteManagement\PrincipalGroup as PrincipalGroupEloquent;
use Application\Models\SiteManagement\PrincipalGroupMembership as MembershipEloquent;
use Application\Models\SiteManagement\PrincipalGroupRoleAttachment as AttachmentEloquent;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

class PrincipalGroupRepository implements PrincipalGroupRepositoryInterface
{
    public function findById(PrincipalGroupIdentifier $principalGroupIdentifier): ?PrincipalGroup
    {
        $model = PrincipalGroupEloquent::query()->find((string) $principalGroupIdentifier);
        if ($model === null) {
            return null;
        }
        $roles = AttachmentEloquent::query()->where('principal_group_id', $model->id)->get()->map(static fn (AttachmentEloquent $attachment): RoleIdentifier => new RoleIdentifier($attachment->role_id))->all();
        $group = new PrincipalGroup($principalGroupIdentifier, $model->name, $roles, new AccountIdentifier($model->account_id), $model->is_default);
        $group->replaceMembers(MembershipEloquent::query()->where('principal_group_id', $model->id)->get()->map(static fn (MembershipEloquent $membership): PrincipalIdentifier => new PrincipalIdentifier($membership->principal_id))->all());

        return $group;
    }

    public function findDefaultByAccountIdentifier(AccountIdentifier $accountIdentifier): ?PrincipalGroup
    {
        $model = PrincipalGroupEloquent::query()->where('account_id', (string) $accountIdentifier)->where('is_default', true)->first();

        return $model === null ? null : $this->findById(new PrincipalGroupIdentifier($model->id));
    }

    public function save(PrincipalGroup $principalGroup): void
    {
        PrincipalGroupEloquent::query()->updateOrCreate(['id' => (string) $principalGroup->principalGroupIdentifier()], ['name' => $principalGroup->name(), 'account_id' => (string) $principalGroup->accountIdentifier(), 'is_default' => $principalGroup->isDefault()]);
        AttachmentEloquent::query()->where('principal_group_id', (string) $principalGroup->principalGroupIdentifier())->delete();
        foreach ($principalGroup->roles() as $identifier) {
            AttachmentEloquent::query()->insert(['principal_group_id' => (string) $principalGroup->principalGroupIdentifier(), 'role_id' => (string) $identifier]);
        }
        MembershipEloquent::query()->where('principal_group_id', (string) $principalGroup->principalGroupIdentifier())->delete();
        foreach ($principalGroup->members() as $member) {
            MembershipEloquent::query()->insert(['principal_group_id' => (string) $principalGroup->principalGroupIdentifier(), 'principal_id' => (string) $member, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /** @return PrincipalGroup[] */
    public function findByPrincipalId(PrincipalIdentifier $principalIdentifier): array
    {
        $ids = MembershipEloquent::query()->where('principal_id', (string) $principalIdentifier)->pluck('principal_group_id');
        $models = PrincipalGroupEloquent::query()->whereIn('id', $ids)->get();
        $attachments = AttachmentEloquent::query()->whereIn('principal_group_id', $models->pluck('id'))->get();
        $memberships = MembershipEloquent::query()->whereIn('principal_group_id', $models->pluck('id'))->get();
        $result = [];
        foreach ($models as $model) {
            $entity = new PrincipalGroup(new PrincipalGroupIdentifier($model->id), $model->name, $attachments->where('principal_group_id', $model->id)->map(static fn (AttachmentEloquent $attachment): RoleIdentifier => new RoleIdentifier($attachment->role_id))->all(), new AccountIdentifier($model->account_id), $model->is_default);
            $entity->replaceMembers($memberships->where('principal_group_id', $model->id)->map(static fn (MembershipEloquent $membership): PrincipalIdentifier => new PrincipalIdentifier($membership->principal_id))->all());
            $result[] = $entity;
        }

        return $result;
    }
}
