<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Repository;

use Application\Models\Wiki\PrincipalGroup as PrincipalGroupEloquent;
use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;

class PrincipalGroupRepository implements PrincipalGroupRepositoryInterface
{
    public function save(PrincipalGroup $principalGroup): void
    {
        PrincipalGroupEloquent::query()->updateOrCreate(
            ['id' => (string) $principalGroup->principalGroupIdentifier()],
            [
                'account_id' => (string) $principalGroup->accountIdentifier(),
                'name' => $principalGroup->name(),
                'is_default' => $principalGroup->isDefault(),
            ]
        );
    }

    public function findById(PrincipalGroupIdentifier $principalGroupIdentifier): ?PrincipalGroup
    {
        $eloquent = PrincipalGroupEloquent::query()
            ->where('id', (string) $principalGroupIdentifier)
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
        $eloquentModels = PrincipalGroupEloquent::query()
            ->where('account_id', (string) $accountIdentifier)
            ->get();

        return $eloquentModels->map(fn (PrincipalGroupEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function findDefaultByAccountId(AccountIdentifier $accountIdentifier): ?PrincipalGroup
    {
        $eloquent = PrincipalGroupEloquent::query()
            ->where('account_id', (string) $accountIdentifier)
            ->where('is_default', true)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function delete(PrincipalGroup $principalGroup): void
    {
        PrincipalGroupEloquent::query()
            ->where('id', (string) $principalGroup->principalGroupIdentifier())
            ->delete();
    }

    private function toDomainEntity(PrincipalGroupEloquent $eloquent): PrincipalGroup
    {
        return new PrincipalGroup(
            new PrincipalGroupIdentifier($eloquent->id),
            new AccountIdentifier($eloquent->account_id),
            $eloquent->name,
            $eloquent->is_default,
            new DateTimeImmutable($eloquent->created_at->toDateTimeString()),
        );
    }
}
