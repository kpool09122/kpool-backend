<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Repository;

use Application\Http\Context\AuthContextCache;
use Application\Models\Account\PrincipalGroup as PrincipalGroupEloquent;
use Application\Models\Account\PrincipalGroupMembership as PrincipalGroupMembershipEloquent;
use DateTimeImmutable;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\Uid\Uuid;

class PrincipalGroupRepository implements PrincipalGroupRepositoryInterface
{
    public function save(PrincipalGroup $principalGroup): void
    {
        $previousMemberIds = PrincipalGroupMembershipEloquent::query()
            ->where('principal_group_id', (string) $principalGroup->principalGroupIdentifier())
            ->pluck('principal_id')
            ->all();

        PrincipalGroupEloquent::query()->updateOrCreate(
            ['id' => (string) $principalGroup->principalGroupIdentifier()],
            [
                'account_id' => (string) $principalGroup->accountIdentifier(),
                'name' => $principalGroup->name(),
                'role' => $principalGroup->role()->value,
                'is_default' => $principalGroup->isDefault(),
            ]
        );

        PrincipalGroupMembershipEloquent::query()
            ->where('principal_group_id', (string) $principalGroup->principalGroupIdentifier())
            ->delete();

        $currentMemberIds = [];
        foreach ($principalGroup->members() as $principal) {
            $currentMemberIds[] = (string) $principal->principalIdentifier();
            PrincipalGroupMembershipEloquent::query()->create([
                'id' => (string) Uuid::v7(),
                'principal_group_id' => (string) $principalGroup->principalGroupIdentifier(),
                'principal_id' => (string) $principal->principalIdentifier(),
            ]);
        }

        $this->forgetAccountContexts(array_unique(array_merge($previousMemberIds, $currentMemberIds)));
    }

    public function findById(PrincipalGroupIdentifier $identifier): ?PrincipalGroup
    {
        $eloquent = PrincipalGroupEloquent::query()
            ->with('members')
            ->where('id', (string) $identifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @return array<PrincipalGroup>
     */
    public function findByAccountId(AccountIdentifier $accountIdentifier): array
    {
        $eloquents = PrincipalGroupEloquent::query()
            ->with('members')
            ->where('account_id', (string) $accountIdentifier)
            ->get();

        return $eloquents->map(fn ($e) => $this->toDomainEntity($e))->all();
    }

    /**
     * @return array<PrincipalGroup>
     */
    public function findByPrincipal(Principal $principal): array
    {
        $eloquents = PrincipalGroupEloquent::query()
            ->with('members')
            ->whereHas('members', function ($query) use ($principal) {
                $query->where('principal_id', (string) $principal->principalIdentifier());
            })
            ->get();

        return $eloquents->map(fn ($e) => $this->toDomainEntity($e))->all();
    }

    /**
     * @return array<PrincipalGroup>
     */
    public function findByAccountIdAndPrincipal(
        AccountIdentifier $accountIdentifier,
        Principal $principal
    ): array {
        $eloquents = PrincipalGroupEloquent::query()
            ->with('members')
            ->where('account_id', (string) $accountIdentifier)
            ->whereHas('members', function ($query) use ($principal) {
                $query->where('principal_id', (string) $principal->principalIdentifier());
            })
            ->get();

        return $eloquents->map(fn ($e) => $this->toDomainEntity($e))->all();
    }

    public function findDefaultByAccountId(AccountIdentifier $accountIdentifier): ?PrincipalGroup
    {
        $eloquent = PrincipalGroupEloquent::query()
            ->with('members')
            ->where('account_id', (string) $accountIdentifier)
            ->where('is_default', true)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByAccountIdAndRole(
        AccountIdentifier $accountIdentifier,
        AccountRole $role
    ): ?PrincipalGroup {
        $eloquent = PrincipalGroupEloquent::query()
            ->with('members')
            ->where('account_id', (string) $accountIdentifier)
            ->where('role', $role->value)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function delete(PrincipalGroup $principalGroup): void
    {
        $memberIds = PrincipalGroupMembershipEloquent::query()
            ->where('principal_group_id', (string) $principalGroup->principalGroupIdentifier())
            ->pluck('principal_id')
            ->all();

        PrincipalGroupEloquent::query()
            ->where('id', (string) $principalGroup->principalGroupIdentifier())
            ->delete();

        $this->forgetAccountContexts($memberIds);
    }

    /** @param array<int, string> $identityIds */
    private function forgetAccountContexts(array $identityIds): void
    {
        foreach ($identityIds as $identityId) {
            app(AuthContextCache::class)->forgetAccount(new IdentityIdentifier($identityId));
        }
    }

    private function toDomainEntity(PrincipalGroupEloquent $eloquent): PrincipalGroup
    {
        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($eloquent->id),
            new AccountIdentifier($eloquent->account_id),
            $eloquent->name,
            AccountRole::from($eloquent->role),
            $eloquent->is_default,
            new DateTimeImmutable($eloquent->created_at->toDateTimeString()),
        );

        /** @var PrincipalGroupMembershipEloquent $member */
        foreach ($eloquent->members as $member) {
            $principalGroup->addMember(new Principal(new IdentityIdentifier($member->principal_id)));
        }

        return $principalGroup;
    }
}
