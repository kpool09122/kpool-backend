<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Repository;

use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountCategoryChangeRequestRepositoryInterface
{
    public function save(AccountCategoryChangeRequest $request): void;

    public function findById(AccountCategoryChangeRequestIdentifier $id): ?AccountCategoryChangeRequest;

    /**
     * @param AccountCategoryChangeRequestStatus[] $statuses
     * @return AccountCategoryChangeRequest[]
     */
    public function findByAccountIdAndStatuses(AccountIdentifier $accountId, array $statuses): array;
}
