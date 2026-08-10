<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\ViewMyAccountDocument;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Models\Account\AccountDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

readonly class ViewMyAccountDocumentAction
{
    private const string DISK = 'verification-documents';

    public function __construct(
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ViewMyAccountDocumentRequest $request): JsonResponse|StreamedResponse
    {
        try {
            /** @var AccountDocument|null $document */
            $document = AccountDocument::query()
                ->where('account_id', (string) $this->accountContext->principal()->accountIdentifier())
                ->where('document_type', $request->documentType())
                ->first();

            if ($document === null) {
                throw new NotFoundHttpException(detail: 'Account document not found.');
            }

            $disk = Storage::disk(self::DISK);

            if (! $disk->exists($document->document_path)) {
                throw new NotFoundHttpException(detail: 'Account document file not found.');
            }

            return $disk->response(
                $document->document_path,
                basename($document->document_path),
                ['Cache-Control' => 'private, no-store'],
            );
        } catch (NotFoundHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }
    }
}
