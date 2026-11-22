<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Repository;

use Source\Account\Account\Domain\Entity\AccountSnapshot;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountSnapshotRepositoryInterface
{
    public function findCurrent(AccountIdentifier $accountIdentifier): ?AccountSnapshot;

    public function findById(AccountSnapshotIdentifier $snapshotIdentifier): ?AccountSnapshot;

    public function save(AccountSnapshot $snapshot): void;
}
