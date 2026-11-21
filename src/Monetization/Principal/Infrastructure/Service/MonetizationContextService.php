<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Infrastructure\Service;

use Application\Http\Context\AuthContextCache;
use Application\Models\Monetization\Principal as PrincipalModel;
use Illuminate\Support\Facades\DB;
use Source\Monetization\Principal\Application\Service\MonetizationContextServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class MonetizationContextService implements MonetizationContextServiceInterface
{
    public function __construct(private AuthContextCache $authContextCache)
    {
    }

    public function forgetForAccount(?AccountIdentifier $accountIdentifier): void
    {
        $forget = function () use ($accountIdentifier): void {
            $query = PrincipalModel::query();
            if ($accountIdentifier !== null) {
                $query->where('account_id', (string) $accountIdentifier);
            }
            foreach ($query->get(['identity_id', 'account_id']) as $principal) {
                $this->authContextCache->forgetMonetization(new IdentityIdentifier($principal->identity_id), new AccountIdentifier($principal->account_id));
            }
        };
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($forget);

            return;
        }
        $forget();
    }

    public function forget(IdentityIdentifier $identityIdentifier): void
    {
        $forget = fn () => $this->authContextCache->forgetMonetization($identityIdentifier);
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($forget);

            return;
        }
        $forget();
    }
}
