<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Repository;

use Application\Models\Account\AccountSnapshot as AccountSnapshotModel;
use Application\Models\Account\AccountSnapshotDocument as AccountSnapshotDocumentModel;
use Source\Account\Account\Domain\Entity\AccountSnapshot;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotDocument;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotProfile;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AccountSnapshotRepository implements AccountSnapshotRepositoryInterface
{
    public function findCurrent(AccountIdentifier $accountIdentifier): ?AccountSnapshot
    {
        $current = AccountSnapshotModel::query()
            ->where('account_id', (string)$accountIdentifier)
            ->orderByDesc('version')
            ->first(['id']);

        return $current === null ? null : $this->findById(new AccountSnapshotIdentifier($current->id));
    }

    public function findById(AccountSnapshotIdentifier $snapshotIdentifier): ?AccountSnapshot
    {
        $m = AccountSnapshotModel::query()->with('documents')->find((string)$snapshotIdentifier);
        if ($m === null) {
            return null;
        }

        return new AccountSnapshot(
            new AccountSnapshotIdentifier($m->id),
            new AccountIdentifier($m->account_id),
            $m->version,
            new AccountSnapshotProfile($m->account_email, $m->account_type, $m->account_name, $m->account_status, $m->account_phone, $m->account_address_country_code, $m->account_address_administrative_area_code, $m->account_address_postal_code, $m->account_address_locality, $m->account_address_line1, $m->account_address_line2),
            $m->documents->map(static fn (AccountSnapshotDocumentModel $d): AccountSnapshotDocument => new AccountSnapshotDocument(DocumentType::from($d->document_type), new DocumentPath($d->document_path), $d->uploaded_at->toDateTimeImmutable(), $d->content_hash))->all(),
            $m->saved_at->toDateTimeImmutable(),
        );
    }

    public function save(AccountSnapshot $snapshot): void
    {
        $a = $snapshot->account();
        $m = AccountSnapshotModel::query()->create([
         'id' => (string)$snapshot->snapshotIdentifier(),'account_id' => (string)$snapshot->accountIdentifier(),'version' => $snapshot->version(),'saved_at' => $snapshot->savedAt(),
         'account_email' => $a->email(),'account_type' => $a->type(),'account_name' => $a->name(),'account_status' => $a->status(),'account_phone' => $a->phone(),
         'account_address_country_code' => $a->countryCode(),'account_address_administrative_area_code' => $a->administrativeAreaCode(),
         'account_address_postal_code' => $a->postalCode(),'account_address_locality' => $a->locality(),'account_address_line1' => $a->addressLine1(),'account_address_line2' => $a->addressLine2(),
        ]);
        foreach ($snapshot->documents() as $d) {
            $m->documents()->create(['document_type' => $d->documentType()->value,'document_path' => (string)$d->documentPath(),'uploaded_at' => $d->uploadedAt(),'content_hash' => $d->contentHash()]);
        }
    }
}
