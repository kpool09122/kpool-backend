<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange;

use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeReason;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RequestAccountSnapshotChangeInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;

    public function changeReason(): AccountSnapshotChangeReason;
}
