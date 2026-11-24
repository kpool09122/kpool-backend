<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange;

use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeReason;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class RequestAccountSnapshotChangeInput implements RequestAccountSnapshotChangeInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private Principal $principal, private AccountSnapshotChangeReason $changeReason)
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

    public function changeReason(): AccountSnapshotChangeReason
    {
        return $this->changeReason;
    }
}
