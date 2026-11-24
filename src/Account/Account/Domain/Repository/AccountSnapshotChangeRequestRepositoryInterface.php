<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Repository;

use Source\Account\Account\Domain\Entity\AccountSnapshotChangeRequest;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountSnapshotChangeRequestRepositoryInterface
{
    public function save(AccountSnapshotChangeRequest $request): void;

    public function findById(AccountSnapshotChangeRequestIdentifier $id): ?AccountSnapshotChangeRequest;

    /**
     * @param AccountSnapshotChangeRequestStatus[] $statuses
     * @return AccountSnapshotChangeRequest[]
     */
    public function findByAccountIdAndStatuses(AccountIdentifier $accountId, array $statuses): array;
}
