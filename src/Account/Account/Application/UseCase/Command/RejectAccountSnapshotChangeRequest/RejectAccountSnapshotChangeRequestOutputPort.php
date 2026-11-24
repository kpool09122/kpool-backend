<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest;

use Source\Account\Account\Domain\Entity\AccountSnapshotChangeRequest;

interface RejectAccountSnapshotChangeRequestOutputPort
{
    public function setRequest(AccountSnapshotChangeRequest $request): void;
}
