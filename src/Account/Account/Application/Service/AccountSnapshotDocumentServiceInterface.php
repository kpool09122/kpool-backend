<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Service;

use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotDocument;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountSnapshotDocumentServiceInterface
{
    /** @param AccountDocument[] $documents
     * @return array<string, string>
     * @throws DocumentStorageFailedException
     */
    public function hashes(array $documents): array;

    /** @param AccountDocument[] $documents
     * @return AccountSnapshotDocument[]
     * @throws DocumentStorageFailedException
     */
    public function capture(AccountIdentifier $accountIdentifier, AccountSnapshotIdentifier $snapshotIdentifier, array $documents): array;

    /** @param AccountSnapshotDocument[] $documents */
    public function delete(array $documents): void;
}
