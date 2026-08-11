<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Query;

use Application\Models\Account\Account as AccountModel;
use Application\Models\Account\AccountDocument as AccountDocumentModel;
use Source\Account\Account\Application\Exception\AccountDocumentListForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Query\AccountDocumentReadModel;
use Source\Account\Account\Application\UseCase\Query\ListAccountDocuments\ListAccountDocumentsInputPort;
use Source\Account\Account\Application\UseCase\Query\ListAccountDocuments\ListAccountDocumentsInterface;
use Source\Account\Account\Application\UseCase\Query\ListAccountDocuments\ListAccountDocumentsOutputPort;

readonly class ListAccountDocuments implements ListAccountDocumentsInterface
{
    /**
     * @throws AccountNotFoundException
     * @throws AccountDocumentListForbiddenException
     */
    public function process(ListAccountDocumentsInputPort $input, ListAccountDocumentsOutputPort $output): void
    {
        $accountIdentifier = $input->accountIdentifier();

        if ((string) $input->principal()->accountIdentifier() !== (string) $accountIdentifier) {
            throw new AccountDocumentListForbiddenException();
        }

        $accountExists = AccountModel::query()
            ->where('id', (string) $accountIdentifier)
            ->exists();

        if (! $accountExists) {
            throw new AccountNotFoundException();
        }

        $documents = AccountDocumentModel::query()
            ->select(['document_type', 'document_path', 'uploaded_at'])
            ->where('account_id', (string) $accountIdentifier)
            ->orderBy('document_type')
            ->get()
            ->map(static fn (AccountDocumentModel $document): AccountDocumentReadModel => new AccountDocumentReadModel(
                documentType: $document->document_type,
                documentPath: $document->document_path,
                uploadedAt: $document->uploaded_at->format(DATE_ATOM),
            ))
            ->all();

        $output->output($documents);
    }
}
