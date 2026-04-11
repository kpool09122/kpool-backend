<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\OfficialCertification\Command\RequestCertification;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationAlreadyRequestedException;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationOutput;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RequestCertificationAction
{
    public function __construct(
        private RequestCertificationInterface $requestCertification,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RequestCertificationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RequestCertificationInput(
                    ResourceType::from($request->resourceType()),
                    new WikiIdentifier($request->wikiId()),
                    new AccountIdentifier($request->ownerAccountId()),
                );
                $output = new RequestCertificationOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->requestCertification->process($input, $output);
                DB::commit();
            } catch (OfficialCertificationAlreadyRequestedException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('official_certification_already_requested', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
