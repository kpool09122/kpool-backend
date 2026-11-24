<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query;

use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;

readonly class AccountSnapshotChangeRequestReadModel
{
    /** @param array{requestIdentifier:string,snapshotIdentifier:string,accountIdentifier:string,changeReason:string,reviewedSnapshotIdentifier:?string,status:string,requestedAt:string,reviewedBy:?string,reviewedAt:?string,rejectionReason:array{code:string,detail:?string}|null} $summary
     * @param array<string,string|null> $proposal
     * @param list<array{documentType:string,documentPath:string,uploadedAt:string}> $documents
     */
    public function __construct(private array $summary, private AccountSnapshotReadModel $original, private array $proposal, private array $documents)
    {
    }

    /** @return array{requestIdentifier:string,snapshotIdentifier:string,accountIdentifier:string,changeReason:string,reviewedSnapshotIdentifier:?string,status:string,requestedAt:string,reviewedBy:?string,reviewedAt:?string,rejectionReason:array{code:string,detail:?string}|null} */
    public function summary(): array
    {
        return $this->summary;
    }

    /** @return array{request:array{requestIdentifier:string,snapshotIdentifier:string,accountIdentifier:string,changeReason:string,reviewedSnapshotIdentifier:?string,status:string,requestedAt:string,reviewedBy:?string,reviewedAt:?string,rejectionReason:array{code:string,detail:?string}|null},original:array{snapshotIdentifier:string,accountIdentifier:string,status:string,savedAt:string,isCurrent:bool,account:array<string,string|null>,documents:list<array{documentType:string,uploadedAt:string}>},proposal:array{account:array<string,string|null>,documents:list<array{documentType:string,uploadedAt:string}>}} */
    public function toArray(): array
    {
        return ['request' => $this->summary, 'original' => $this->original->toArray(), 'proposal' => ['account' => $this->proposal, 'documents' => array_map(static fn (array $d): array => ['documentType' => $d['documentType'], 'uploadedAt' => $d['uploadedAt']], $this->documents)]];
    }

    public function documentPath(DocumentType $type, bool $original = false): ?DocumentPath
    {
        if ($original) {
            return $this->original->documentPath($type);
        }
        foreach ($this->documents as $document) {
            if ($document['documentType'] === $type->value) {
                return new DocumentPath($document['documentPath']);
            }
        }

        return null;
    }
}
