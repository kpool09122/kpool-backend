<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Affiliation\Command\ApproveAffiliation;

use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationInterface;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationOutput;
use Source\Account\Affiliation\Domain\Exception\InvalidAffiliationApprovalException;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ApproveAffiliationAction
{
    public function __construct(
        private ApproveAffiliationInterface $approveAffiliation,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ApproveAffiliationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ApproveAffiliationInput(
                    affiliationIdentifier: new AffiliationIdentifier($request->affiliationId()),
                    approverAccountIdentifier: new AccountIdentifier($request->approverAccountIdentifier()),
                );
                $output = new ApproveAffiliationOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->approveAffiliation->process($input, $output);
                DB::commit();
            } catch (AffiliationNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('affiliation_not_found', $language), previous: $e);
            } catch (DisallowedAffiliationOperationException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed_affiliation_operation', $language), previous: $e);
            } catch (InvalidAffiliationApprovalException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_affiliation_approval', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
