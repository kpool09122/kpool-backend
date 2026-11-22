<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot;

use Source\Account\Account\Domain\Entity\AccountSnapshot;

interface SaveAccountSnapshotOutputPort
{
    public function setSnapshot(AccountSnapshot $snapshot): void;
}
