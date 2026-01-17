<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Agency\Command\ApproveAgency;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency\ApproveAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency\ApproveAgencyInterface;
use Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency\ApproveAgencyOutput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ApproveAgencyAction
{
    public function __construct(
        private ApproveAgencyInterface $approveAgency,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ApproveAgencyRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ApproveAgencyRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ApproveAgencyInput(
                    new AgencyIdentifier($request->agencyId()),
                    new PrincipalIdentifier($request->principalId()),
                );
                $output = new ApproveAgencyOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->approveAgency->process($input, $output);
                DB::commit();
            } catch (AgencyNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('agency_not_found', $language), previous: $e);
            } catch (DisallowedException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (InvalidStatusException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('allow_only_under_review_status', $language), previous: $e);
            } catch (ExistsApprovedButNotTranslatedAgencyException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('exists_approved_but_not_translated_agency', $language), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                DB::rollBack();

                throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|ForbiddenHttpException|ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string)$e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string)$e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
