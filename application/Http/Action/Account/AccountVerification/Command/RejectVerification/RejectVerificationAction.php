<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\AccountVerification\Command\RejectVerification;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\AccountVerification\Application\Exception\AccountVerificationNotFoundException;
use Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification\RejectVerificationInput;
use Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification\RejectVerificationInterface;
use Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification\RejectVerificationOutput;
use Source\Account\AccountVerification\Domain\Exception\InvalidVerificationRejectionException;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReason;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReasonCode;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class RejectVerificationAction
{
    public function __construct(
        private RejectVerificationInterface $rejectVerification,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RejectVerificationRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RejectVerificationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RejectVerificationInput(
                    verificationIdentifier: new VerificationIdentifier($request->verificationId()),
                    reviewerAccountIdentifier: new AccountIdentifier($request->reviewerAccountIdentifier()),
                    rejectionReason: new RejectionReason(
                        code: RejectionReasonCode::from($request->rejectionReasonCode()),
                        detail: $request->rejectionReasonDetail(),
                    ),
                );
                $output = new RejectVerificationOutput();
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->rejectVerification->process($input, $output);
                DB::commit();
            } catch (AccountVerificationNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('account_verification_not_found', $language), previous: $e);
            } catch (InvalidVerificationRejectionException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_verification_rejection', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
