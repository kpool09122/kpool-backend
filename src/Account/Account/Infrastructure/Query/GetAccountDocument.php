<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Query;

use Application\Models\Account\Account as AccountModel;
use Application\Models\Account\AccountDocument as AccountDocumentModel;
use Source\Account\Account\Application\Exception\AccountDocumentNotFoundException;
use Source\Account\Account\Application\Exception\AccountDocumentViewForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Query\AccountDocumentReadModel;
use Source\Account\Account\Application\UseCase\Query\GetAccountDocument\GetAccountDocumentInputPort;
use Source\Account\Account\Application\UseCase\Query\GetAccountDocument\GetAccountDocumentInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class GetAccountDocument implements GetAccountDocumentInterface
{
    public function __construct(private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    public function process(GetAccountDocumentInputPort $input): AccountDocumentReadModel
    {
        $operatorAccountIdentifier = $input->principal()->accountIdentifier();
        if (! $this->policyEvaluator->evaluate(
            $input->principal(),
            Action::ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE,
            Resource::account($operatorAccountIdentifier),
        )) {
            throw new AccountDocumentViewForbiddenException();
        }

        $accountExists = AccountModel::query()
            ->where('id', (string) $input->accountIdentifier())
            ->exists();

        if (! $accountExists) {
            throw new AccountNotFoundException();
        }

        /** @var AccountDocumentModel|null $document */
        $document = AccountDocumentModel::query()
            ->select(['document_type', 'document_path', 'uploaded_at'])
            ->where('account_id', (string) $input->accountIdentifier())
            ->where('document_type', $input->documentType())
            ->first();

        if ($document === null) {
            throw new AccountDocumentNotFoundException();
        }

        return new AccountDocumentReadModel(
            documentType: $document->document_type,
            documentPath: $document->document_path,
            uploadedAt: $document->uploaded_at->format(DATE_ATOM),
        );
    }
}
