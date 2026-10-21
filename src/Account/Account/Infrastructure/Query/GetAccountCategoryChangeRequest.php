<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Query;

use Application\Models\Account\AccountCategoryChangeRequest as AccountCategoryChangeRequestModel;
use Application\Models\Account\AccountCategoryChangeRequestDocument;
use DateTimeInterface;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestDetailReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountDocumentReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountReadModel;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestInputPort;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestOutputPort;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class GetAccountCategoryChangeRequest implements GetAccountCategoryChangeRequestInterface
{
    public function __construct(private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    public function process(GetAccountCategoryChangeRequestInputPort $input, GetAccountCategoryChangeRequestOutputPort $output): void
    {
        $reviewerAccountIdentifier = $input->principal()->accountIdentifier();
        if (! $this->policyEvaluator->evaluate(
            $input->principal(),
            Action::ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE,
            Resource::account($reviewerAccountIdentifier),
        )) {
            throw new AccountCategoryChangeRequestForbiddenException();
        }
        $request = AccountCategoryChangeRequestModel::query()->with('documents')
            ->where('id', (string) $input->requestIdentifier())->first();
        if ($request === null) {
            throw new AccountCategoryChangeRequestNotFoundException();
        }
        $output->output(new AccountCategoryChangeRequestDetailReadModel(
            request: new AccountCategoryChangeRequestReadModel(
                requestIdentifier: $request->id,
                accountIdentifier: $request->account_id,
                currentAccountCategory: $request->current_account_category,
                requestedAccountCategory: $request->requested_account_category,
                status: $request->status,
                requestedAt: $request->requested_at->format(DateTimeInterface::ATOM),
                reviewedBy: $request->reviewed_by,
                reviewedAt: $request->reviewed_at?->format(DateTimeInterface::ATOM),
                rejectionReason: $request->rejection_reason,
            ),
            account: new AccountReadModel(
                accountIdentifier: $request->account_id,
                email: $request->account_email,
                type: $request->account_type,
                name: $request->account_name,
                status: $request->account_status,
                accountCategory: $request->current_account_category,
                phone: $request->account_phone,
                address: [
                    'countryCode' => $request->account_address_country_code,
                    'administrativeAreaCode' => $request->account_address_administrative_area_code,
                    'postalCode' => $request->account_address_postal_code,
                    'locality' => $request->account_address_locality,
                    'addressLine1' => $request->account_address_line1,
                    'addressLine2' => $request->account_address_line2,
                ],
            ),
            documents: $request->documents->map(
                static fn (AccountCategoryChangeRequestDocument $document): AccountDocumentReadModel => new AccountDocumentReadModel(
                    $document->document_type,
                    $document->document_path,
                    $document->uploaded_at->format(DateTimeInterface::ATOM),
                ),
            )->all(),
        ));
    }
}
