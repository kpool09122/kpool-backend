<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Service;

use Application\Http\Context\AuthContextCache;
use Application\Models\Account\Principal as PrincipalEloquent;
use Illuminate\Support\Facades\DB;
use Source\Account\Account\Application\Service\AccountContextServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AccountContextService implements AccountContextServiceInterface
{
    public function __construct(private AuthContextCache $authContextCache)
    {
    }

    public function forget(IdentityIdentifier $identityIdentifier): void
    {
        $forget = fn () => $this->authContextCache->forgetAccount($identityIdentifier);
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($forget);

            return;
        }
        $forget();
    }

    public function forgetByAccountIdentifier(AccountIdentifier $accountIdentifier): void
    {
        $identityIds = PrincipalEloquent::query()
            ->where('account_id', (string) $accountIdentifier)
            ->get(['identity_id'])->map(static fn (PrincipalEloquent $principal): string => $principal->identity_id)
            ->all();

        foreach ($identityIds as $identityId) {
            $this->authContextCache->forgetAccount(new IdentityIdentifier($identityId));
        }
    }
}
