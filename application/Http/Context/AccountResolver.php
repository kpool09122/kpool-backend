<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Application\Models\Account\PrincipalGroup as PrincipalGroupModel;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class AccountResolver
{
    /** @throws AccountNotFoundException */
    public function resolve(IdentityIdentifier $identityIdentifier): AccountContext
    {
        /** @var object{account_id: string, role: string}|null $row */
        $row = PrincipalGroupModel::query()
            ->select(['account_principal_groups.account_id', 'account_principal_groups.role'])
            ->join(
                'account_principal_group_memberships',
                'account_principal_groups.id',
                '=',
                'account_principal_group_memberships.principal_group_id'
            )
            ->where('account_principal_group_memberships.principal_id', (string) $identityIdentifier)
            ->first();

        if ($row === null) {
            throw new AccountNotFoundException('Account context not found.');
        }

        return new AccountContext(
            accountIdentifier: new AccountIdentifier($row->account_id),
            role: AccountRole::from($row->role),
        );
    }
}
