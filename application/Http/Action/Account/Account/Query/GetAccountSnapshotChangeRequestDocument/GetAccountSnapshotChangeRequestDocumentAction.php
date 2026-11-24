<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetAccountSnapshotChangeRequestDocument;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestOutput;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use ValueError;

readonly class GetAccountSnapshotChangeRequestDocumentAction
{
    private const string DISK = 'verification-documents';

    public function __construct(
        private GetAccountSnapshotChangeRequestInterface $getAccountSnapshotChangeRequest,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(GetAccountSnapshotChangeRequestDocumentRequest $request): JsonResponse|StreamedResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new GetAccountSnapshotChangeRequestInput(
                    requestIdentifier: new AccountSnapshotChangeRequestIdentifier($request->requestId()),
                    principal: $this->accountContext->principal(),
                );
                $documentType = DocumentType::from($request->documentType());
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('disallowed', $language), previous: $e);
            }

            try {
                $output = new GetAccountSnapshotChangeRequestOutput();
                $this->getAccountSnapshotChangeRequest->process($input, $output);
            } catch (AccountSnapshotChangeRequestNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('account_snapshot_change_request_not_found', $language), previous: $e);
            } catch (AccountSnapshotNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('account_snapshot_not_found', $language), previous: $e);
            } catch (AccountSnapshotChangeRequestForbiddenException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            }

            $disk = Storage::disk(self::DISK);
            $path = $output->request()?->documentPath($documentType, $request->documentSource() === 'original');
            $documentPath = $path !== null ? (string) $path : null;

            if ($documentPath === null || ! $disk->exists($documentPath)) {
                throw new NotFoundHttpException(detail: error_message('account_snapshot_not_found', $language));
            }

            return $disk->response(
                $documentPath,
                basename($documentPath),
                ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'],
            );
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }
    }
}
