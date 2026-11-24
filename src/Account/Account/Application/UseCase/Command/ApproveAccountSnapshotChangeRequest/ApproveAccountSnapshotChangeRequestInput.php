<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;

readonly class ApproveAccountSnapshotChangeRequestInput implements ApproveAccountSnapshotChangeRequestInputPort
{
    public function __construct(private AccountSnapshotChangeRequestIdentifier $requestIdentifier, private Principal $principal)
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
}
