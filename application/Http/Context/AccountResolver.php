<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Application\Models\Account\Principal as PrincipalModel;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class AccountResolver
{
    /** @throws AccountNotFoundException */
    public function resolve(IdentityIdentifier $identityIdentifier): AccountContext
    {
        $principal = PrincipalModel::query()
            ->where('identity_id', (string) $identityIdentifier)
            ->first();

        if ($principal === null) {
            throw new AccountNotFoundException('Account context not found.');
        }

        return new AccountContext(
            principal: new Principal(
                new PrincipalIdentifier($principal->id),
                new IdentityIdentifier($principal->identity_id),
                new AccountIdentifier($principal->account_id),
            ),
        );
    }
}
