<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\AccountVerification\Command\ApproveVerification;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\AccountVerification\Application\Exception\AccountVerificationNotFoundException;
use Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification\ApproveVerificationInput;
use Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification\ApproveVerificationInterface;
use Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification\ApproveVerificationOutput;
use Source\Account\AccountVerification\Domain\Exception\InvalidVerificationApprovalException;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ApproveVerificationAction
{
    public function __construct(
        private ApproveVerificationInterface $approveVerification,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ApproveVerificationRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ApproveVerificationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ApproveVerificationInput(
                    verificationIdentifier: new VerificationIdentifier($request->verificationId()),
                    reviewerAccountIdentifier: new AccountIdentifier($request->reviewerAccountIdentifier()),
                );
                $output = new ApproveVerificationOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->approveVerification->process($input, $output);
                DB::commit();
            } catch (AccountVerificationNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('account_verification_not_found', $language), previous: $e);
            } catch (InvalidVerificationApprovalException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_verification_approval', $language), previous: $e);
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
