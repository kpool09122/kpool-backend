<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Factory;

use Source\Account\Account\Domain\Entity\AccountSnapshot;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotDocument;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotProfile;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountSnapshotFactoryInterface
{
    public function createIdentifier(): AccountSnapshotIdentifier;

    /** @param AccountSnapshotDocument[] $documents */
    public function create(AccountSnapshotIdentifier $snapshotIdentifier, AccountIdentifier $accountIdentifier, AccountSnapshotStatus $status, AccountSnapshotProfile $account, array $documents): AccountSnapshot;
}
