<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Infrastructure\Service;

use Application\Models\Account\Principal as PrincipalModel;
use Source\Monetization\Principal\Application\Service\AccountMembershipServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AccountMembershipService implements AccountMembershipServiceInterface
{
    public function isAccountOwner(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): bool
    {
        return PrincipalModel::query()
            ->join('account_principal_group_memberships as memberships', 'memberships.principal_id', '=', 'account_principals.id')
            ->join('account_principal_groups as groups', 'groups.id', '=', 'memberships.principal_group_id')
            ->join('account_principal_group_role_attachments as attachments', 'attachments.principal_group_id', '=', 'groups.id')
            ->join('account_roles as roles', 'roles.id', '=', 'attachments.role_id')
            ->where('account_principals.identity_id', (string) $identityIdentifier)
            ->where('account_principals.account_id', (string) $accountIdentifier)
            ->where('groups.account_id', (string) $accountIdentifier)
            ->where('roles.name', 'Owner')
            ->whereNull('roles.account_id')
            ->exists();
    }

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
