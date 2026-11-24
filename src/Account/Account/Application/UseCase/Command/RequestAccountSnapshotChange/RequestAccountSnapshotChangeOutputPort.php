<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange;

use Source\Account\Account\Domain\Entity\AccountSnapshotChangeRequest;

interface RequestAccountSnapshotChangeOutputPort
{
    public function setRequest(AccountSnapshotChangeRequest $request): void;
}
