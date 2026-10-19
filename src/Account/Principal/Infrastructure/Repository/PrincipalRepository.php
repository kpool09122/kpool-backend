<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Repository;

use Application\Http\Context\AuthContextCache;
use Application\Models\Account\Principal as PrincipalEloquent;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

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

        $ids = array_map(static fn (PrincipalIdentifier $principalIdentifier): string => (string) $principalIdentifier, $principalIdentifiers);

        $eloquents = PrincipalEloquent::query()
            ->whereIn('id', $ids)
            ->get();

        $result = [];
        foreach ($eloquents as $eloquent) {
            $result[$eloquent->id] = $this->toDomainEntity($eloquent);
        }

        return $result;
    }

    /** @return Principal[] */
    public function findByAccountId(AccountIdentifier $accountIdentifier): array
    {
        return PrincipalEloquent::query()
            ->where('account_id', (string) $accountIdentifier)
            ->get()
            ->map(fn (PrincipalEloquent $eloquent): Principal => $this->toDomainEntity($eloquent))
            ->all();
    }

    public function findByAccountIds(array $accountIdentifiers): array
    {
        if ($accountIdentifiers === []) {
            return [];
        }

        return PrincipalEloquent::query()
            ->whereIn('account_id', array_map(static fn (AccountIdentifier $identifier): string => (string) $identifier, $accountIdentifiers))
            ->get()
            ->map(fn (PrincipalEloquent $eloquent): Principal => $this->toDomainEntity($eloquent))
            ->all();
    }

    public function findAllByIdentityIdentifiers(array $identityIdentifiers): array
    {
        if ($identityIdentifiers === []) {
            return [];
        }

        return PrincipalEloquent::query()
            ->whereIn('identity_id', array_map(static fn (IdentityIdentifier $identifier): string => (string) $identifier, $identityIdentifiers))
            ->get()
            ->map(fn (PrincipalEloquent $eloquent): Principal => $this->toDomainEntity($eloquent))
            ->all();
    }

    /** @return Principal[] */
    public function findOwnersByAccountId(AccountIdentifier $accountIdentifier): array
    {
        return PrincipalEloquent::query()
            ->join('account_principal_group_memberships as memberships', 'memberships.principal_id', '=', 'account_principals.id')
            ->join('account_principal_groups as groups', 'groups.id', '=', 'memberships.principal_group_id')
            ->join('account_principal_group_role_attachments as attachments', 'attachments.principal_group_id', '=', 'groups.id')
            ->join('account_roles as roles', 'roles.id', '=', 'attachments.role_id')
            ->where('account_principals.account_id', (string) $accountIdentifier)
            ->where('groups.account_id', (string) $accountIdentifier)
            ->where('roles.name', Role::OWNER)
            ->whereNull('roles.account_id')
            ->select('account_principals.*')
            ->distinct()
            ->get()
            ->map(fn (PrincipalEloquent $eloquent): Principal => $this->toDomainEntity($eloquent))
            ->all();
    }

    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): ?Principal
    {
        $eloquent = PrincipalEloquent::query()
            ->where('identity_id', (string) $identityIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /** @return Principal[] */
    public function findAllByIdentityIdentifier(IdentityIdentifier $identityIdentifier): array
    {
        return PrincipalEloquent::query()
            ->where('identity_id', (string) $identityIdentifier)
            ->get()
            ->map(fn (PrincipalEloquent $eloquent): Principal => $this->toDomainEntity($eloquent))
            ->all();
    }

    public function findByIdentityIdentifierAndAccountIdentifier(
        IdentityIdentifier $identityIdentifier,
        AccountIdentifier $accountIdentifier,
    ): ?Principal {
        $eloquent = PrincipalEloquent::query()
            ->where('identity_id', (string) $identityIdentifier)
            ->where('account_id', (string) $accountIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByEmailAndAccountIdentifier(Email $email, AccountIdentifier $accountIdentifier): ?Principal
    {
        $eloquent = PrincipalEloquent::query()
            ->join('identities', 'account_principals.identity_id', '=', 'identities.id')
            ->where('identities.email', (string) $email)
            ->where('account_principals.account_id', (string) $accountIdentifier)
            ->select('account_principals.*')
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function save(Principal $principal): void
    {
        $previousIdentityId = PrincipalEloquent::query()
            ->where('id', (string) $principal->principalIdentifier())
            ->first(['identity_id'])?->identity_id;

        PrincipalEloquent::query()->updateOrCreate(
            ['id' => (string) $principal->principalIdentifier()],
            [
                'identity_id' => (string) $principal->identityIdentifier(),
                'account_id' => (string) $principal->accountIdentifier(),
                'status' => $principal->status()->value,
            ],
        );

        foreach (array_filter([$previousIdentityId, (string) $principal->identityIdentifier()]) as $identityId) {
            app(AuthContextCache::class)->forgetAccount(new IdentityIdentifier($identityId));
        }
    }

    public function deleteByIdentityIdentifier(IdentityIdentifier $identityIdentifier): void
    {
        PrincipalEloquent::query()->where('identity_id', (string) $identityIdentifier)->delete();
    }

    private function toDomainEntity(PrincipalEloquent $eloquent): Principal
    {
        return new Principal(
            new PrincipalIdentifier($eloquent->id),
            new IdentityIdentifier($eloquent->identity_id),
            new AccountIdentifier($eloquent->account_id),
            PrincipalStatus::from($eloquent->status),
        );
    }
}
