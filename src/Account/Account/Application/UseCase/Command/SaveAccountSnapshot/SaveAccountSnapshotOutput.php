<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot;

use DateTimeInterface;
use Source\Account\Account\Domain\Entity\AccountSnapshot;

class SaveAccountSnapshotOutput implements SaveAccountSnapshotOutputPort
{
    private ?AccountSnapshot $snapshot = null;

    public function setSnapshot(AccountSnapshot $snapshot): void
    {
        $this->snapshot = $snapshot;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {

        if ($this->snapshot === null) {
            return [];
        }
        $s = $this->snapshot;

        return ['snapshotIdentifier' => (string)$s->snapshotIdentifier(),'accountIdentifier' => (string)$s->accountIdentifier(),'version' => $s->version(),'savedAt' => $s->savedAt()->format(DateTimeInterface::ATOM)];

    }
}
