<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\UploadDocuments;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountDocumentUploadForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\DocumentData;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocumentsInput;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocumentsInterface;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocumentsOutput;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class UploadDocumentsAction
{
    public function __construct(
        private UploadDocumentsInterface $useCase,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UploadDocumentsRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $accountIdentifier = new AccountIdentifier($request->accountId());

                $documents = array_map(
                    static function (array $doc): DocumentData {
                        $decoded = base64_decode($doc['fileContents'], true);
                        if ($decoded === false) {
                            throw new InvalidArgumentException('Invalid base64 encoding in fileContents.');
                        }

                        return new DocumentData(
                            documentType: DocumentType::from($doc['documentType']),
                            fileContents: $decoded,
                        );
                    },
                    $request->documents(),
                );
                $input = new UploadDocumentsInput(
                    $accountIdentifier,
                    $this->accountContext->principal(),
                    $documents,
                );
                $output = new UploadDocumentsOutput();
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->useCase->process($input, $output);
                DB::commit();
            } catch (AccountNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: $e->getMessage(), previous: $e);
            } catch (AccountDocumentUploadForbiddenException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (InvalidDocumentsForVerificationException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            } catch (DocumentStorageFailedException $e) {
                DB::rollBack();

                throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException|NotFoundHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
