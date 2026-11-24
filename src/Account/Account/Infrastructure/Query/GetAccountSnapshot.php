<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Query;

use Application\Models\Account\AccountSnapshot as AccountSnapshotModel;
use Application\Models\Account\AccountSnapshotDocument;
use DateTimeInterface;
use Source\Account\Account\Application\Exception\AccountSnapshotForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\UseCase\Query\AccountSnapshotReadModel;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot\GetAccountSnapshotInputPort;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot\GetAccountSnapshotInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class GetAccountSnapshot implements GetAccountSnapshotInterface
{
    public function __construct(private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    public function process(GetAccountSnapshotInputPort $input): AccountSnapshotReadModel
    {
        $owner = (string)$input->principal()->accountIdentifier() === (string)$input->accountIdentifier();
        if (! $this->policyEvaluator->evaluate($input->principal(), $owner ? Action::READ : Action::ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE, Resource::account($owner ? $input->accountIdentifier() : $input->principal()->accountIdentifier()))) {
            throw new AccountSnapshotForbiddenException();
        }
        $currentId = AccountSnapshotModel::query()->where('account_id', (string)$input->accountIdentifier())->where('status', 'approved')->orderByDesc('saved_at')->orderByDesc('id')->value('id');
        $query = AccountSnapshotModel::query()->with('documents')->where('account_id', (string)$input->accountIdentifier());
        if ($input->snapshotIdentifier() !== null) {
            $query->where('id', (string)$input->snapshotIdentifier());
        } else {
            $query->where('id', $currentId);
        }
        $m = $query->first();
        if ($m === null) {
            throw new AccountSnapshotNotFoundException();
        }
        $isCurrent = $m->id === $currentId;

        return new AccountSnapshotReadModel(
            $m->id,
            $m->account_id,
            $m->status,
            $m->saved_at->format(DateTimeInterface::ATOM),
            $isCurrent,
            ['email' => $m->account_email,'type' => $m->account_type,'name' => $m->account_name,'status' => $m->account_status,'phone' => $m->account_phone,'countryCode' => $m->account_address_country_code,'administrativeAreaCode' => $m->account_address_administrative_area_code,'postalCode' => $m->account_address_postal_code,'locality' => $m->account_address_locality,'addressLine1' => $m->account_address_line1,'addressLine2' => $m->account_address_line2],
            array_values($m->documents->map(static fn (AccountSnapshotDocument $d): array => ['documentType' => $d->document_type,'documentPath' => $d->document_path,'uploadedAt' => $d->uploaded_at->format(DateTimeInterface::ATOM)])->all())
        );
    }
}
