<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Service;

use Application\Models\Account\AccountCategoryChangeRequest as AccountCategoryChangeRequestModel;
use Application\Models\Account\AccountCategoryChangeRequestDocument;
use Illuminate\Support\Str;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\Service\AccountCategoryChangeRequestSnapshotServiceInterface;
use Source\Account\Account\Application\Service\DocumentStorageServiceInterface;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Throwable;

readonly class AccountCategoryChangeRequestSnapshotService implements AccountCategoryChangeRequestSnapshotServiceInterface
{
    public function __construct(private DocumentStorageServiceInterface $documentStorageService)
    {
    }

    public function capture(AccountCategoryChangeRequest $request, Account $account): void
    {
        $documents = [];
        $copiedPaths = [];

        try {
            foreach ($account->documents()->all() as $document) {
                $path = new DocumentPath(sprintf(
                    'accounts/%s/category-change-requests/%s/%s_%s.%s',
                    (string) $account->accountIdentifier(),
                    (string) $request->requestIdentifier(),
                    $document->documentType()->value,
                    (string) Str::uuid(),
                    pathinfo((string) $document->documentPath(), PATHINFO_EXTENSION),
                ));
                $this->documentStorageService->copy($document->documentPath(), $path);
                $copiedPaths[] = $path;
                $documents[] = [
                    'document_type' => $document->documentType()->value,
                    'document_path' => (string) $path,
                    'uploaded_at' => $document->uploadedAt(),
                ];
            }
            $requestModel = AccountCategoryChangeRequestModel::query()->findOrFail((string) $request->requestIdentifier());
            $requestModel->documents()->createMany($documents);
        } catch (Throwable $exception) {
            foreach ($copiedPaths as $path) {
                $this->documentStorageService->delete($path);
            }

            throw new DocumentStorageFailedException('Failed to capture account category change request snapshot.', $exception);
        }
    }

    public function deleteForAccount(AccountIdentifier $accountIdentifier): void
    {
        $query = AccountCategoryChangeRequestDocument::query()->whereIn(
            'request_id',
            AccountCategoryChangeRequestModel::query()->select('id')->where('account_id', (string) $accountIdentifier),
        );
        foreach ($query->get() as $document) {
            $this->documentStorageService->delete(new DocumentPath($document->document_path));
        }
        $query->delete();
    }
}
