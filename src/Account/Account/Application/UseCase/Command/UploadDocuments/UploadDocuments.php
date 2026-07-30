<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UploadDocuments;

use DateTimeImmutable;
use Source\Account\Account\Application\Exception\AccountDocumentUploadForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\Service\AccountDocumentFileTypeDetectorInterface;
use Source\Account\Account\Application\Service\DocumentStorageServiceInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Service\AccountDocumentRequirementValidatorInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Throwable;

readonly class UploadDocuments implements UploadDocumentsInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private DocumentStorageServiceInterface $storageService,
        private AccountDocumentFileTypeDetectorInterface $fileTypeDetector,
        private AccountDocumentRequirementValidatorInterface $documentRequirementValidator,
    ) {
    }

    public function process(UploadDocumentsInputPort $input, UploadDocumentsOutputPort $output): void
    {
        $account = $this->accountRepository->findById($input->accountIdentifier());
        if ($account === null) {
            throw new AccountNotFoundException();
        }

        if ((string) $input->principal()->accountIdentifier() !== (string) $account->accountIdentifier()) {
            throw new AccountDocumentUploadForbiddenException();
        }

        $this->documentRequirementValidator->validate(
            $account->type(),
            array_map(static fn (DocumentData $document) => $document->documentType, $input->documents()),
        );

        $storedPaths = [];
        $documents = [];
        $fileTypes = [];
        $oldPaths = array_map(
            static fn (AccountDocument $document) => $document->documentPath(),
            $account->documents()->all(),
        );

        foreach ($input->documents() as $index => $documentData) {
            $fileTypes[$index] = $this->fileTypeDetector->detect($documentData->fileContents);
        }

        try {
            foreach ($input->documents() as $index => $documentData) {
                $documentPath = $this->storageService->storeForAccount(
                    $input->accountIdentifier(),
                    $documentData->documentType,
                    $fileTypes[$index],
                    $documentData->fileContents,
                );
                $storedPaths[] = $documentPath;
                $documents[] = new AccountDocument(
                    $input->accountIdentifier(),
                    $documentData->documentType,
                    $documentPath,
                    new DateTimeImmutable(),
                );
            }
        } catch (Throwable $e) {
            foreach ($storedPaths as $path) {
                $this->storageService->delete($path);
            }

            throw new DocumentStorageFailedException('Failed to store account documents: ' . $e->getMessage(), $e);
        }

        try {
            $account->replaceDocuments($documents);
            $this->accountRepository->save($account);
        } catch (Throwable $e) {
            foreach ($storedPaths as $path) {
                $this->storageService->delete($path);
            }

            throw $e;
        }

        foreach ($oldPaths as $path) {
            $this->storageService->deleteAfterCommit($path);
        }

        $output->setDocuments($documents);
    }
}
