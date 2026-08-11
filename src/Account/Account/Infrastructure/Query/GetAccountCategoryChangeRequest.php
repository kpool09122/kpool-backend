<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Query;

use Application\Models\Account\Account as AccountModel;
use Application\Models\Account\AccountCategoryChangeRequest as AccountCategoryChangeRequestModel;
use Application\Models\Account\AccountDocument as AccountDocumentModel;
use Application\Models\Account\Principal as PrincipalModel;
use DateTimeInterface;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestDetailReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestIdentityReadModel;
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

        /** @var AccountCategoryChangeRequestModel|null $request */
        $request = AccountCategoryChangeRequestModel::query()
            ->select([
                'id',
                'account_id',
                'current_account_category',
                'requested_account_category',
                'status',
                'requested_at',
                'reviewed_by',
                'reviewed_at',
                'rejection_reason',
            ])
            ->where('id', (string) $input->requestIdentifier())
            ->first();

        if ($request === null) {
            throw new AccountCategoryChangeRequestNotFoundException();
        }

        /** @var AccountModel|null $account */
        $account = AccountModel::query()
            ->select(['id', 'email', 'type', 'name', 'status', 'category', 'phone', 'address'])
            ->where('id', $request->account_id)
            ->first();

        if ($account === null) {
            throw new AccountNotFoundException();
        }

        $identities = PrincipalModel::query()
            ->select('account_principals.*')
            ->with('identity')
            ->where('account_id', $request->account_id)
            ->join('identities', 'identities.id', '=', 'account_principals.identity_id')
            ->orderBy('identities.identity_name')
            ->get()
            ->map(static fn (PrincipalModel $principal): AccountCategoryChangeRequestIdentityReadModel => new AccountCategoryChangeRequestIdentityReadModel(
                name: $principal->identity->identity_name,
                email: $principal->identity->email,
            ))
            ->values()
            ->all();

        $documents = AccountDocumentModel::query()
            ->select(['document_type', 'document_path', 'uploaded_at'])
            ->where('account_id', $request->account_id)
            ->orderBy('document_type')
            ->get()
            ->map(static fn (AccountDocumentModel $document): AccountDocumentReadModel => new AccountDocumentReadModel(
                documentType: $document->document_type,
                documentPath: $document->document_path,
                uploadedAt: $document->uploaded_at->format(DateTimeInterface::ATOM),
            ))
            ->values()
            ->all();

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
                rejectionReason: self::rejectionReason($request->rejection_reason),
            ),
            account: new AccountReadModel(
                accountIdentifier: $account->id,
                email: $account->email,
                type: $account->type,
                name: $account->name,
                status: $account->status,
                accountCategory: $account->category,
                phone: $account->phone,
                address: $account->address,
            ),
            identities: $identities,
            documents: $documents,
        ));
    }

    /**
     * @param array<string, string|null>|null $rejectionReason
     * @return array{code: string, detail: ?string}|null
     */
    private static function rejectionReason(?array $rejectionReason): ?array
    {
        if ($rejectionReason === null) {
            return null;
        }

        return [
            'code' => (string) $rejectionReason['code'],
            'detail' => isset($rejectionReason['detail']) ? (string) $rejectionReason['detail'] : null,
        ];
    }
}
