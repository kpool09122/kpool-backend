<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest;

use DateTimeInterface;
use Source\Account\Account\Domain\Entity\AccountSnapshotChangeRequest;

class RejectAccountSnapshotChangeRequestOutput implements RejectAccountSnapshotChangeRequestOutputPort
{
    private ?AccountSnapshotChangeRequest $request = null;

    public function setRequest(AccountSnapshotChangeRequest $request): void
    {
        $this->request = $request;
    }

    /** @return array{}|array{requestIdentifier: string, snapshotIdentifier: string, accountIdentifier: string, changeReason: string, reviewedSnapshotIdentifier: ?string, status: string, requestedAt: string, reviewedBy: ?string, reviewedAt: ?string, rejectionReason: array{code:string,detail:?string}|null} */
    public function toArray(): array
    {
        if ($this->request === null) {
            return [];
        }
        $request = $this->request;

        return [
            'requestIdentifier' => (string) $request->requestIdentifier(),
            'snapshotIdentifier' => (string) $request->snapshotIdentifier(),
            'accountIdentifier' => (string) $request->accountIdentifier(),
            'changeReason' => (string) $request->changeReason(),
            'reviewedSnapshotIdentifier' => $request->reviewedSnapshotIdentifier() !== null ? (string) $request->reviewedSnapshotIdentifier() : null,
            'status' => $request->status()->value,
            'requestedAt' => $request->requestedAt()->format(DateTimeInterface::ATOM),
            'reviewedBy' => $request->reviewedBy() !== null ? (string) $request->reviewedBy() : null,
            'reviewedAt' => $request->reviewedAt()?->format(DateTimeInterface::ATOM),
            'rejectionReason' => $request->rejectionReason()?->toArray(),
        ];
    }
}
