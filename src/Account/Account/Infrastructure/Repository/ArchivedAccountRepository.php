<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Repository;

use Application\Models\Account\ArchivedAccount as ArchivedAccountEloquent;
use Source\Account\Account\Domain\Entity\ArchivedAccount;
use Source\Account\Account\Domain\Repository\ArchivedAccountRepositoryInterface;

readonly class ArchivedAccountRepository implements ArchivedAccountRepositoryInterface
{
    public function save(ArchivedAccount $archivedAccount): void
    {
        ArchivedAccountEloquent::query()->create([
            'id' => (string) $archivedAccount->archivedAccountIdentifier(),
            'account_id' => (string) $archivedAccount->accountIdentifier(),
            'account_category' => $archivedAccount->accountCategory()->value,
            'account_type' => $archivedAccount->accountType()->value,
            'archived_at' => $archivedAccount->archivedAt(),
        ]);
    }
}
