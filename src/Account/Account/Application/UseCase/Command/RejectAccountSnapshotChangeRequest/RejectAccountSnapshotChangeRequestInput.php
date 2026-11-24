<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Principal\Domain\Entity\Principal;

readonly class RejectAccountSnapshotChangeRequestInput implements RejectAccountSnapshotChangeRequestInputPort
{
    public function __construct(private AccountSnapshotChangeRequestIdentifier $requestIdentifier, private Principal $principal, private RejectionReason $rejectionReason)
    {
    }

    public function requestIdentifier(): AccountSnapshotChangeRequestIdentifier
    {
        return $this->requestIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function rejectionReason(): RejectionReason
    {
        return $this->rejectionReason;
    }
}
