<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot;

use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class GetAccountSnapshotInput implements GetAccountSnapshotInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private Principal $principal, private ?AccountSnapshotIdentifier $snapshotIdentifier = null)
    {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function snapshotIdentifier(): ?AccountSnapshotIdentifier
    {
        return $this->snapshotIdentifier;
    }
}
