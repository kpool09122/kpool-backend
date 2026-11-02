<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Infrastructure\Repository;

use Application\Models\Analyze\Principal as PrincipalEloquent;
use Source\Analyze\Principal\Domain\Entity\Principal;
use Source\Analyze\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PrincipalRepository implements PrincipalRepositoryInterface
{
    public function save(Principal $principal): void
    {
        PrincipalEloquent::query()->updateOrCreate(['id' => (string) $principal->principalIdentifier()], ['identity_id' => (string) $principal->identityIdentifier(), 'account_id' => (string) $principal->accountIdentifier(), 'status' => $principal->status()->value]);

    }

    /** @param PrincipalIdentifier[] $principalIdentifiers
     * @return array<string, Principal> */
    public function findByIds(array $principalIdentifiers): array
    {
        $models = PrincipalEloquent::query()->whereIn('id', array_map(static fn (PrincipalIdentifier $id): string => (string) $id, $principalIdentifiers))->get();
        $result = [];
        foreach ($models as $model) {
            $result[$model->id] = new Principal(new PrincipalIdentifier($model->id), new IdentityIdentifier($model->identity_id), new AccountIdentifier($model->account_id), PrincipalStatus::from($model->status));
        }

        return $result;
    }

    public function findById(PrincipalIdentifier $principalIdentifier): ?Principal
    {
        $model = PrincipalEloquent::query()->find((string) $principalIdentifier);

        return $model === null ? null : new Principal(new PrincipalIdentifier($model->id), new IdentityIdentifier($model->identity_id), new AccountIdentifier($model->account_id), PrincipalStatus::from($model->status));
    }

    public function findByIdentityIdentifierAndAccountIdentifier(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): ?Principal
    {
        $model = PrincipalEloquent::query()->where('identity_id', (string) $identityIdentifier)->where('account_id', (string) $accountIdentifier)->first();

        return $model === null ? null : new Principal(new PrincipalIdentifier($model->id), new IdentityIdentifier($model->identity_id), new AccountIdentifier($model->account_id), PrincipalStatus::from($model->status));
    }

    /** @return Principal[] */
    public function findAllByIdentityIdentifier(IdentityIdentifier $identityIdentifier): array
    {
        return PrincipalEloquent::query()->where('identity_id', (string) $identityIdentifier)->get()
            ->map(static fn (PrincipalEloquent $model): Principal => new Principal(
                new PrincipalIdentifier($model->id),
                new IdentityIdentifier($model->identity_id),
                new AccountIdentifier($model->account_id),
                PrincipalStatus::from($model->status),
            ))->all();
    }

    public function delete(Principal $principal): void
    {
        PrincipalEloquent::query()->where('id', (string) $principal->principalIdentifier())->delete();
    }
}
