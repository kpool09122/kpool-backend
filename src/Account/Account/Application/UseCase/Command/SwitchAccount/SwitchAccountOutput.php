<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\SwitchAccount;

use LogicException;
use Source\Account\Account\Application\Service\CurrentAccount;

class SwitchAccountOutput implements SwitchAccountOutputPort
{
    private ?CurrentAccount $currentAccount = null;

    public function setCurrentAccount(CurrentAccount $currentAccount): void
    {
        $this->currentAccount = $currentAccount;
    }

    public function currentAccount(): CurrentAccount
    {
        return $this->currentAccount ?? throw new LogicException('Current account has not been set.');
    }

    /** @return array{originalIdentityIdentifier: string, accountIdentifier: string, accountPrincipalIdentifier: string, delegationIdentifier: ?string} */
    public function toArray(): array
    {
        $currentAccount = $this->currentAccount();

        return [
            'originalIdentityIdentifier' => (string) $currentAccount->originalIdentityIdentifier,
            'accountIdentifier' => (string) $currentAccount->effectiveAccountIdentifier,
            'accountPrincipalIdentifier' => (string) $currentAccount->effectivePrincipalIdentifier,
            'delegationIdentifier' => $currentAccount->delegationIdentifier !== null ? (string) $currentAccount->delegationIdentifier : null,
        ];
    }
}
