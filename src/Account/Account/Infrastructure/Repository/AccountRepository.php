<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Repository;

use Application\Models\Account\Account as AccountEloquent;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;

class AccountRepository implements AccountRepositoryInterface
{
    public function save(Account $account): void
    {
        AccountEloquent::query()->updateOrCreate(
            ['id' => (string) $account->accountIdentifier()],
            [
                'email' => (string) $account->email(),
                'type' => $account->type()->value,
                'name' => (string) $account->name(),
                'status' => $account->status()->value,
                'category' => $account->accountCategory()->value,
            ]
        );
    }

    public function findById(AccountIdentifier $identifier): ?Account
    {
        $eloquent = AccountEloquent::query()
            ->where('id', (string) $identifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByEmail(Email $email): ?Account
    {
        $eloquent = AccountEloquent::query()
            ->where('email', (string) $email)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function delete(Account $account): void
    {
        AccountEloquent::query()
            ->where('id', (string) $account->accountIdentifier())
            ->delete();
    }

    private function toDomainEntity(AccountEloquent $eloquent): Account
    {
        return new Account(
            new AccountIdentifier($eloquent->id),
            new Email($eloquent->email),
            AccountType::from($eloquent->type),
            new AccountName($eloquent->name),
            AccountStatus::from($eloquent->status),
            AccountCategory::from($eloquent->category),
            DeletionReadinessChecklist::ready(),
        );
    }
}
