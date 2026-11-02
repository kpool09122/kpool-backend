<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Infrastructure\Service;

use Application\Models\Account\Principal as PrincipalModel;
use Source\Analyze\Principal\Application\Service\AccountMembershipServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AccountMembershipService implements AccountMembershipServiceInterface
{
    public function belongsToActiveAccount(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): bool
    {
        return PrincipalModel::query()->from('account_principals as principals')
            ->join('accounts', 'accounts.id', '=', 'principals.account_id')
            ->join('account_principal_group_memberships as memberships', 'memberships.principal_id', '=', 'principals.id')
            ->join('account_principal_groups as groups', 'groups.id', '=', 'memberships.principal_group_id')
            ->where('principals.identity_id', (string) $identityIdentifier)
            ->where('principals.account_id', (string) $accountIdentifier)
            ->where('groups.account_id', (string) $accountIdentifier)
            ->where('principals.status', 'active')
            ->where('accounts.status', 'active')
            ->exists();
    }
}
