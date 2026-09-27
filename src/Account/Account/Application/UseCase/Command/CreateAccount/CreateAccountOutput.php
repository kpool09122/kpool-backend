<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Account\Domain\Entity\Account;

class CreateAccountOutput implements CreateAccountOutputPort
{
    private ?Account $account = null;

    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    /**
     * @return array{}|array{accountIdentifier: string, email: string, type: string|null, name: string, status: string, accountCategory: string, phone: string|null, address: array{countryCode: string|null, administrativeAreaCode: string|null, postalCode: string|null, locality: string|null, addressLine1: string|null, addressLine2: string|null}|null}
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
            'type' => $this->account->type()?->value,
            'name' => (string) $account->name(),
            'status' => $account->status()->value,
            'accountCategory' => $account->accountCategory()->value,
            'phone' => $account->phone() !== null ? (string) $account->phone() : null,
            'address' => $account->address()?->toArray(),
        ];
    }
}
