<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotDocument;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotProfile;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AccountSnapshot
{
    /** @param AccountSnapshotDocument[] $documents */
    public function __construct(
        private readonly AccountSnapshotIdentifier $snapshotIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly AccountSnapshotStatus $status,
        private readonly AccountSnapshotProfile $account,
        private readonly array $documents,
        private readonly DateTimeImmutable $savedAt,
    ) {
    }

    public function snapshotIdentifier(): AccountSnapshotIdentifier
    {
        return $this->snapshotIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function status(): AccountSnapshotStatus
    {
        return $this->status;
    }

    public function account(): AccountSnapshotProfile
    {
        return $this->account;
    }

    /** @return AccountSnapshotDocument[] */
    public function documents(): array
    {
        return $this->documents;
    }

    public function savedAt(): DateTimeImmutable
    {
        return $this->savedAt;
    }

    /** @param array<string, string> $documentHashes */
    public function hasSameContents(AccountSnapshotProfile $account, array $documentHashes): bool
    {
        $stored = [];
        foreach ($this->documents as $document) {
            $stored[$document->documentType()->value] = $document->contentHash();
        }
        ksort($stored);
        ksort($documentHashes);

        return $this->account == $account && $stored === $documentHashes;
    }
}
