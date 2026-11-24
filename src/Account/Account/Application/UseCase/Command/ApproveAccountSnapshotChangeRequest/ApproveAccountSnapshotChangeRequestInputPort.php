<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;

interface ApproveAccountSnapshotChangeRequestInputPort
{
    public function requestIdentifier(): AccountSnapshotChangeRequestIdentifier;

    public function principal(): Principal;
}
