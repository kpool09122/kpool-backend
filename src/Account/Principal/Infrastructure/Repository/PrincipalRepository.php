<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Repository;

use Application\Http\Context\AuthContextCache;
use Application\Models\Account\Principal as PrincipalEloquent;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
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
            ],
        );

        foreach (array_filter([$previousIdentityId, (string) $principal->identityIdentifier()]) as $identityId) {
            app(AuthContextCache::class)->forgetAccount(new IdentityIdentifier($identityId));
        }
    }

    private function toDomainEntity(PrincipalEloquent $eloquent): Principal
    {
        return new Principal(
            new PrincipalIdentifier($eloquent->id),
            new IdentityIdentifier($eloquent->identity_id),
            new AccountIdentifier($eloquent->account_id),
        );
    }
}
