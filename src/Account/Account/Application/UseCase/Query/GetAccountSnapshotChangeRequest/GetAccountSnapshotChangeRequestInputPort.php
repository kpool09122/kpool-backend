<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;

interface GetAccountSnapshotChangeRequestInputPort
{
    public function requestIdentifier(): AccountSnapshotChangeRequestIdentifier;

    public function principal(): Principal;
}
