<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Repository;

use Application\Http\Context\AuthContextCache;
use Application\Models\Wiki\Principal as PrincipalEloquent;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class PrincipalRepository implements PrincipalRepositoryInterface
{
    public function findById(PrincipalIdentifier $principalIdentifier): ?Principal
    {
        $eloquent = PrincipalEloquent::query()
            ->where('id', (string) $principalIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @param array<int, PrincipalIdentifier> $principalIdentifiers
     * @return array<string, Principal>
     */
    public function findByIds(array $principalIdentifiers): array
    {
        if (empty($principalIdentifiers)) {
            return [];
        }

        $ids = array_map(
            static fn (PrincipalIdentifier $id) => (string) $id,
            $principalIdentifiers
        );

        $eloquents = PrincipalEloquent::query()
            ->whereIn('id', $ids)
            ->get();

        $result = [];
        foreach ($eloquents as $eloquent) {
            $result[$eloquent->id] = $this->toDomainEntity($eloquent);
        }

        return $result;
    }

    /**
     * @return Principal[]
     */
    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): array
    {
        $eloquents = PrincipalEloquent::query()
            ->where('identity_id', (string) $identityIdentifier)
            ->orderBy('created_at')
            ->get();

        return $eloquents->map(fn (PrincipalEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function findByIdentityIdentifierAndAccountIdentifier(
        IdentityIdentifier $identityIdentifier,
        AccountIdentifier $accountIdentifier,
    ): ?Principal {
        $eloquent = PrincipalEloquent::query()
            ->where('wiki_principals.identity_id', (string) $identityIdentifier)
            ->where('wiki_principals.account_id', (string) $accountIdentifier)
            ->first();

        return $eloquent !== null ? $this->toDomainEntity($eloquent) : null;
    }

    /**
     * @return Principal[]
     */
    public function findByDelegation(DelegationIdentifier $delegationIdentifier): array
    {
        $eloquents = PrincipalEloquent::query()
            ->where('delegation_identifier', (string) $delegationIdentifier)
            ->get();

        return $eloquents->map(fn (PrincipalEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    /**
     * @return Principal[]
     */
    public function findByAccountId(AccountIdentifier $accountIdentifier): array
    {
        $eloquents = PrincipalEloquent::query()
            ->where('account_id', (string) $accountIdentifier)
            ->get();

        return $eloquents->map(fn (PrincipalEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function save(Principal $principal): void
    {
        $previousIdentityId = PrincipalEloquent::query()
            ->where('id', (string) $principal->principalIdentifier())
            ->value('identity_id');

        PrincipalEloquent::query()->updateOrCreate(
            ['id' => (string) $principal->principalIdentifier()],
            [
                'identity_id' => (string) $principal->identityIdentifier(),
                'account_id' => (string) $principal->accountIdentifier(),
                'delegation_identifier' => $principal->delegationIdentifier() !== null
                    ? (string) $principal->delegationIdentifier()
                    : null,
                'enabled' => $principal->isEnabled(),
            ]
        );

        foreach (array_unique(array_filter([$previousIdentityId, (string) $principal->identityIdentifier()])) as $identityId) {
            app(AuthContextCache::class)->forgetWiki(new IdentityIdentifier($identityId));
        }
    }

    public function deleteByDelegation(DelegationIdentifier $delegationIdentifier): void
    {
        $identityIds = PrincipalEloquent::query()
            ->where('delegation_identifier', (string) $delegationIdentifier)
            ->pluck('identity_id')
            ->all();

        PrincipalEloquent::query()
            ->where('delegation_identifier', (string) $delegationIdentifier)
            ->delete();

        foreach ($identityIds as $identityId) {
            app(AuthContextCache::class)->forgetWiki(new IdentityIdentifier($identityId));
        }
    }

    private function toDomainEntity(PrincipalEloquent $eloquent): Principal
    {
        return new Principal(
            new PrincipalIdentifier($eloquent->id),
            new IdentityIdentifier($eloquent->identity_id),
            new AccountIdentifier($eloquent->account_id),
            $eloquent->delegation_identifier !== null
                ? new DelegationIdentifier($eloquent->delegation_identifier)
                : null,
            $eloquent->enabled,
        );
    }
}
