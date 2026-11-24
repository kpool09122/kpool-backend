<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest;

use Source\Account\Account\Domain\Entity\AccountSnapshotChangeRequest;

interface ApproveAccountSnapshotChangeRequestOutputPort
{
    public function setRequest(AccountSnapshotChangeRequest $request): void;
}
