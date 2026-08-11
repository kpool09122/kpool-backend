<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\ViewAccountDocument;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountDocumentNotFoundException;
use Source\Account\Account\Application\Exception\AccountDocumentViewForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Query\GetAccountDocument\GetAccountDocumentInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountDocument\GetAccountDocumentInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

readonly class ViewAccountDocumentAction
{
    private const string DISK = 'verification-documents';

    public function __construct(
        private GetAccountDocumentInterface $getAccountDocument,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ViewAccountDocumentRequest $request): JsonResponse|StreamedResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new GetAccountDocumentInput(
                    accountIdentifier: new AccountIdentifier($request->accountId()),
                    documentType: $request->documentType(),
                    principal: $this->accountContext->principal(),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $document = $this->getAccountDocument->process($input);
            } catch (AccountDocumentNotFoundException|AccountNotFoundException $e) {
                throw new NotFoundHttpException(detail: $e->getMessage(), previous: $e);
            } catch (AccountDocumentViewForbiddenException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            }

            $disk = Storage::disk(self::DISK);
            $documentPath = $document->documentPath();

            if (! $disk->exists($documentPath)) {
                throw new NotFoundHttpException(detail: 'Account document file not found.');
            }

            return $disk->response(
                $documentPath,
                basename($documentPath),
                ['Cache-Control' => 'private, no-store'],
            );
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }
    }
}
