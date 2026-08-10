<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Repository;

use Source\Account\Account\Domain\Entity\AccountTypeChangeRequest;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountTypeChangeRequestRepositoryInterface
{
    public function save(AccountTypeChangeRequest $request): void;

    public function findById(AccountTypeChangeRequestIdentifier $id): ?AccountTypeChangeRequest;

    public function findPendingByAccountId(AccountIdentifier $accountId): ?AccountTypeChangeRequest;

    public function existsPending(AccountIdentifier $accountId): bool;
}
