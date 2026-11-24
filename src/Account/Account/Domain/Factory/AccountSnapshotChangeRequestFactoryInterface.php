<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Factory;

use DateTimeImmutable;
use Source\Account\Account\Domain\Entity\AccountSnapshotChangeRequest;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeReason;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountSnapshotChangeRequestFactoryInterface
{
    public function createIdentifier(): AccountSnapshotChangeRequestIdentifier;

    public function create(AccountSnapshotChangeRequestIdentifier $requestIdentifier, AccountIdentifier $accountIdentifier, AccountSnapshotIdentifier $snapshotIdentifier, AccountSnapshotChangeReason $changeReason, DateTimeImmutable $requestedAt): AccountSnapshotChangeRequest;
}
