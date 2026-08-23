<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Service;

use Application\Http\Context\AuthContextCache;
use Application\Models\Account\Principal as PrincipalEloquent;
use Source\Account\Account\Application\Service\AccountContextInvalidationServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AccountContextInvalidationService implements AccountContextInvalidationServiceInterface
{
    public function __construct(private AuthContextCache $cache)
    {
    }

    public function forgetByAccountIdentifier(AccountIdentifier $accountIdentifier): void
    {
        $identityIds = PrincipalEloquent::query()
            ->where('account_id', (string) $accountIdentifier)
            ->pluck('identity_id')
            ->all();

        foreach ($identityIds as $identityId) {
            $this->cache->forgetAccount(new IdentityIdentifier($identityId));
        }
    }
}
