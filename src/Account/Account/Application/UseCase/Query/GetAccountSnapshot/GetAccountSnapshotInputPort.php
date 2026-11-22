<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot;

use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface GetAccountSnapshotInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;

    public function snapshotIdentifier(): ?AccountSnapshotIdentifier;
}
