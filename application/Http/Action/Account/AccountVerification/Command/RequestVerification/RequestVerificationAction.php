<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\AccountVerification\Command\RequestVerification;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\AccountVerification\Application\Exception\AccountVerificationAlreadyRequestedException;
use Source\Account\AccountVerification\Application\Exception\DocumentStorageFailedException;
use Source\Account\AccountVerification\Application\Exception\InvalidAccountCategoryForVerificationException;
use Source\Account\AccountVerification\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification\DocumentData;
use Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification\RequestVerificationInput;
use Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification\RequestVerificationInterface;
use Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification\RequestVerificationOutput;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentType;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class RequestVerificationAction
{
    public function __construct(
        private RequestVerificationInterface $requestVerification,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RequestVerificationRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RequestVerificationRequest $request): JsonResponse
    {
        try {
            try {
                $documents = array_map(
                    static function (array $doc): DocumentData {
                        $decoded = base64_decode($doc['fileContents'], true);

                        if ($decoded === false) {
                            throw new InvalidArgumentException('Invalid base64 encoding in fileContents.');
                        }

                        return new DocumentData(
                            documentType: DocumentType::from($doc['documentType']),
                            fileName: $doc['fileName'],
                            fileContents: $decoded,
                            fileSizeBytes: (int) $doc['fileSizeBytes'],
                        );
                    },
                    $request->documents(),
                );

                $input = new RequestVerificationInput(
                    accountIdentifier: new AccountIdentifier($request->accountIdentifier()),
                    verificationType: VerificationType::from($request->verificationType()),
                    applicantInfo: new ApplicantInfo($request->applicantName()),
                    documents: $documents,
                );
                $output = new RequestVerificationOutput();
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->requestVerification->process($input, $output);
                DB::commit();
            } catch (InvalidAccountCategoryForVerificationException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_account_category_for_verification', $language), previous: $e);
            } catch (AccountVerificationAlreadyRequestedException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('account_verification_already_requested', $language), previous: $e);
            } catch (InvalidDocumentsForVerificationException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_documents_for_verification', $language), previous: $e);
            } catch (DocumentStorageFailedException $e) {
                DB::rollBack();

                throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
