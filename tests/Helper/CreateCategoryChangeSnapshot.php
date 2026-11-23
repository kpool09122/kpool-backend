<?php

declare(strict_types=1);

namespace Tests\Helper;

use Application\Models\Account\Account;
use Application\Models\Account\AccountSnapshot as AccountSnapshotModel;
use Source\Account\Account\Application\Service\AccountSnapshotDocumentServiceInterface;
use Source\Account\Account\Domain\Entity\Account as AccountEntity;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class CreateCategoryChangeSnapshot
{
    public static function create(AccountIdentifier $accountIdentifier): AccountSnapshotIdentifier
    {
        $account = Account::query()->findOrFail((string) $accountIdentifier);
        $id = new AccountSnapshotIdentifier(StrTestHelper::generateUuid());
        AccountSnapshotModel::query()->create([
            ...CreateAccountSnapshotProfile::columns(),
            'id' => (string) $id,
            'account_id' => (string) $accountIdentifier,
            'version' => (AccountSnapshotModel::query()->where('account_id', (string) $accountIdentifier)->orderByDesc('version')->first()->version ?? 0) + 1,
            'saved_at' => '2026-08-11 10:00:00',
            'account_email' => $account->email,
            'account_type' => $account->type,
            'account_name' => $account->name,
            'account_status' => $account->status,
            'account_phone' => $account->phone ?? '+819****5678',
            'account_address_country_code' => $account->address_country_code ?? 'JP',
            'account_address_administrative_area_code' => $account->address_administrative_area_code,
            'account_address_postal_code' => $account->address_postal_code ?? '100-0001',
            'account_address_locality' => $account->address_locality,
            'account_address_line1' => $account->address_line1 ?? 'Address',
            'account_address_line2' => $account->address_line2,
        ]);

        return $id;
    }

    public static function capture(AccountSnapshotIdentifier $id, AccountEntity $account): void
    {
        $documents = app(AccountSnapshotDocumentServiceInterface::class)->capture($account->accountIdentifier(), $id, $account->documents()->all());
        $snapshot = AccountSnapshotModel::query()->findOrFail((string) $id);
        foreach ($documents as $document) {
            $snapshot->documents()->create(['document_type' => $document->documentType()->value, 'document_path' => (string) $document->documentPath(), 'uploaded_at' => $document->uploadedAt(), 'content_hash' => $document->contentHash()]);
        }
    }
}
