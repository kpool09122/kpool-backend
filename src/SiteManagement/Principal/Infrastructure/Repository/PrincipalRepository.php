<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Infrastructure\Repository;

use Application\Http\Context\AuthContextCache;
use Application\Models\SiteManagement\Principal as PrincipalEloquent;
use Illuminate\Support\Facades\DB;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

class PrincipalRepository implements PrincipalRepositoryInterface
{
    public function save(Principal $principal): void
    {
        $previousPrincipal = $this->findById($principal->principalIdentifier());
        PrincipalEloquent::query()->updateOrCreate(['id' => (string) $principal->principalIdentifier()], ['identity_id' => (string) $principal->identityIdentifier(), 'account_id' => (string) $principal->accountIdentifier()]);
        $this->forgetContext($principal->identityIdentifier(), $principal->accountIdentifier());
        if ($previousPrincipal !== null && ((string) $previousPrincipal->identityIdentifier() !== (string) $principal->identityIdentifier() || (string) $previousPrincipal->accountIdentifier() !== (string) $principal->accountIdentifier())) {
            $this->forgetContext($previousPrincipal->identityIdentifier(), $previousPrincipal->accountIdentifier());
        }
    }

    public function findById(PrincipalIdentifier $principalIdentifier): ?Principal
    {
        $model = PrincipalEloquent::query()->find((string) $principalIdentifier);

        return $model === null ? null : new Principal(new PrincipalIdentifier($model->id), new IdentityIdentifier($model->identity_id), new AccountIdentifier($model->account_id));
    }

    public function findByIdentityIdentifierAndAccountIdentifier(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): ?Principal
    {
        $model = PrincipalEloquent::query()->where('identity_id', (string) $identityIdentifier)->where('account_id', (string) $accountIdentifier)->first();

        return $model === null ? null : new Principal(new PrincipalIdentifier($model->id), new IdentityIdentifier($model->identity_id), new AccountIdentifier($model->account_id));
    }

    /** @return Principal[] */
    public function findAllByIdentityIdentifier(IdentityIdentifier $identityIdentifier): array
    {
        return PrincipalEloquent::query()->where('identity_id', (string) $identityIdentifier)->get()
            ->map(static fn (PrincipalEloquent $model): Principal => new Principal(
                new PrincipalIdentifier($model->id),
                new IdentityIdentifier($model->identity_id),
                new AccountIdentifier($model->account_id),
            ))->all();
    }

    public function delete(Principal $principal): void
    {
        PrincipalEloquent::query()->where('id', (string) $principal->principalIdentifier())->delete();
        $this->forgetContext($principal->identityIdentifier(), $principal->accountIdentifier());
    }

    private function forgetContext(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): void
    {
        $forget = fn () => app(AuthContextCache::class)->forgetSiteManagement($identityIdentifier, $accountIdentifier);
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($forget);

            return;
        }

        $forget();
    }
}
