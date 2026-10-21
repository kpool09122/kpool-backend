<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetAccountCategoryChangeRequestDocument;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestOutput;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

readonly class GetAccountCategoryChangeRequestDocumentAction
{
    private const string DISK = 'verification-documents';

    public function __construct(
        private GetAccountCategoryChangeRequestInterface $getAccountCategoryChangeRequest,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(GetAccountCategoryChangeRequestDocumentRequest $request): JsonResponse|StreamedResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new GetAccountCategoryChangeRequestInput(
                    requestIdentifier: new AccountCategoryChangeRequestIdentifier($request->requestId()),
                    principal: $this->accountContext->principal(),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $output = new GetAccountCategoryChangeRequestOutput();
                $this->getAccountCategoryChangeRequest->process($input, $output);
            } catch (AccountCategoryChangeRequestNotFoundException $e) {
                throw new NotFoundHttpException(detail: $e->getMessage(), previous: $e);
            } catch (AccountCategoryChangeRequestForbiddenException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            }

            $disk = Storage::disk(self::DISK);
            $documentPath = null;
            foreach ($output->toArray()['documents'] as $document) {
                if ($document['documentType'] === $request->documentType()) {
                    $documentPath = $document['documentPath'];

                    break;
                }
            }

            if ($documentPath === null || ! $disk->exists($documentPath)) {
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
