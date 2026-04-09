<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\DeleteAccount;

use Source\Account\Account\Domain\Entity\Account;

class DeleteAccountOutput implements DeleteAccountOutputPort
{
    private ?Account $account = null;

    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->account === null) {
            return [];
        }

        $account = $this->account;

        return [
            'accountIdentifier' => (string) $account->accountIdentifier(),
            'email' => (string) $account->email(),
            'type' => $account->type()->value,
            'name' => (string) $account->name(),
            'status' => $account->status()->value,
        ];
    }
}
