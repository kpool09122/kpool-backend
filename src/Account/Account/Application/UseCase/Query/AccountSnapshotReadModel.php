<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query;

use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;

readonly class AccountSnapshotReadModel
{
    /** @param array<string, string|null> $account
     * @param list<array{documentType:string, documentPath:string, uploadedAt:string}> $documents
     */
    public function __construct(private string $snapshotIdentifier, private string $accountIdentifier, private int $version, private string $savedAt, private bool $isCurrent, private array $account, private array $documents)
    {
    }

    public function snapshotIdentifier(): string
    {
        return $this->snapshotIdentifier;
    }

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function savedAt(): string
    {
        return $this->savedAt;
    }

    public function isCurrent(): bool
    {
        return $this->isCurrent;
    }

    /** @return array<string, string|null> */
    public function account(): array
    {
        return $this->account;
    }

    /** @return array{snapshotIdentifier:string,accountIdentifier:string,version:int,savedAt:string,isCurrent:bool,account:array<string,string|null>,documents:list<array{documentType:string,uploadedAt:string}>} */
    public function toArray(): array
    {
        return ['snapshotIdentifier' => $this->snapshotIdentifier,'accountIdentifier' => $this->accountIdentifier,'version' => $this->version,'savedAt' => $this->savedAt,'isCurrent' => $this->isCurrent,'account' => $this->account,'documents' => array_map(static fn (array $d): array => ['documentType' => $d['documentType'],'uploadedAt' => $d['uploadedAt']], $this->documents)];
    }

    public function documentPath(DocumentType $documentType): ?DocumentPath
    {
        foreach ($this->documents as $document) {
            if ($document['documentType'] === $documentType->value) {
                return new DocumentPath($document['documentPath']);
            }
        }

        return null;
    }
}
