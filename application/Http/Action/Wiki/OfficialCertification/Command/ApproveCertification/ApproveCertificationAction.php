<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\OfficialCertification\Command\ApproveCertification;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification\ApproveCertificationInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification\ApproveCertificationInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification\ApproveCertificationOutput;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ApproveCertificationAction
{
    public function __construct(
        private ApproveCertificationInterface $approveCertification,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ApproveCertificationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ApproveCertificationInput(
                    new CertificationIdentifier($request->certificationId()),
                );
                $output = new ApproveCertificationOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->approveCertification->process($input, $output);
                DB::commit();
            } catch (OfficialCertificationNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('official_certification_not_found', $language), previous: $e);
            } catch (OfficialCertificationInvalidStatusException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('official_certification_invalid_status', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
