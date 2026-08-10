<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Repository;

use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountCategoryChangeRequestRepositoryInterface
{
    public function save(AccountCategoryChangeRequest $request): void;

    public function findById(AccountCategoryChangeRequestIdentifier $id): ?AccountCategoryChangeRequest;

    public function findPendingByAccountId(AccountIdentifier $accountId): ?AccountCategoryChangeRequest;
}
