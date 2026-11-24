<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Factory;

use Application\Models\Account\AccountDocument;
use Application\Models\Account\AccountSnapshotChangeRequest;
use Application\Models\Account\AccountSnapshotDocument;
use DateTimeInterface;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\UseCase\Query\AccountSnapshotChangeRequestReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountSnapshotReadModel;

class AccountSnapshotChangeRequestReadModelFactory
{
    /** @throws AccountSnapshotNotFoundException */
    public function create(AccountSnapshotChangeRequest $request): AccountSnapshotChangeRequestReadModel
    {
        $snapshot = $request->snapshot ?? throw new AccountSnapshotNotFoundException();

        $reviewed = $request->status === 'pending' ? null : ($request->reviewedSnapshot ?? throw new AccountSnapshotNotFoundException());
        $account = $request->account ?? throw new AccountSnapshotNotFoundException();
        $proposal = $reviewed ?? $account;

        return new AccountSnapshotChangeRequestReadModel(
            ['requestIdentifier' => $request->id, 'snapshotIdentifier' => $request->snapshot_id, 'accountIdentifier' => $request->account_id, 'changeReason' => $request->change_reason, 'reviewedSnapshotIdentifier' => $request->reviewed_snapshot_id, 'status' => $request->status, 'requestedAt' => $request->requested_at->format(DateTimeInterface::ATOM), 'reviewedBy' => $request->reviewed_by, 'reviewedAt' => $request->reviewed_at?->format(DateTimeInterface::ATOM), 'rejectionReason' => $request->rejection_reason],
            new AccountSnapshotReadModel(
                $snapshot->id,
                $snapshot->account_id,
                $snapshot->status,
                $snapshot->saved_at->format(DateTimeInterface::ATOM),
                $snapshot->id === $request->current_snapshot_id,
                ['email' => $snapshot->account_email, 'type' => $snapshot->account_type, 'name' => $snapshot->account_name, 'status' => $snapshot->account_status, 'phone' => $snapshot->account_phone, 'countryCode' => $snapshot->account_address_country_code, 'administrativeAreaCode' => $snapshot->account_address_administrative_area_code, 'postalCode' => $snapshot->account_address_postal_code, 'locality' => $snapshot->account_address_locality, 'addressLine1' => $snapshot->account_address_line1, 'addressLine2' => $snapshot->account_address_line2],
                array_values($snapshot->documents->map(static fn (AccountSnapshotDocument $document): array => ['documentType' => $document->document_type, 'documentPath' => $document->document_path, 'uploadedAt' => $document->uploaded_at->format(DateTimeInterface::ATOM)])->all()),
            ),
            $reviewed === null ? ['email' => $account->email, 'type' => $account->type, 'name' => $account->name, 'status' => $account->status, 'phone' => $account->phone, 'countryCode' => $account->address_country_code, 'administrativeAreaCode' => $account->address_administrative_area_code, 'postalCode' => $account->address_postal_code, 'locality' => $account->address_locality, 'addressLine1' => $account->address_line1, 'addressLine2' => $account->address_line2] : ['email' => $reviewed->account_email, 'type' => $reviewed->account_type, 'name' => $reviewed->account_name, 'status' => $reviewed->account_status, 'phone' => $reviewed->account_phone, 'countryCode' => $reviewed->account_address_country_code, 'administrativeAreaCode' => $reviewed->account_address_administrative_area_code, 'postalCode' => $reviewed->account_address_postal_code, 'locality' => $reviewed->account_address_locality, 'addressLine1' => $reviewed->account_address_line1, 'addressLine2' => $reviewed->account_address_line2],
            array_values($proposal->documents->map(static fn (AccountDocument|AccountSnapshotDocument $document): array => ['documentType' => $document->document_type, 'documentPath' => $document->document_path, 'uploadedAt' => $document->uploaded_at->format(DateTimeInterface::ATOM)])->values()->all()),
        );
    }
}
