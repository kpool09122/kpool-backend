<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Principal\Domain\Entity\Principal;

interface RejectAccountSnapshotChangeRequestInputPort
{
    public function requestIdentifier(): AccountSnapshotChangeRequestIdentifier;

    public function principal(): Principal;

    public function rejectionReason(): RejectionReason;
}
