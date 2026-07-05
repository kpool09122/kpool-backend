<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Query\GetIdentityProfile;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Query\GetIdentityProfile\GetIdentityProfileInput;
use Source\Identity\Application\UseCase\Query\GetIdentityProfile\GetIdentityProfileInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetIdentityProfileAction
{
    public function __construct(
        private GetIdentityProfileInterface $getIdentityProfile,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(GetIdentityProfileRequest $request): JsonResponse
    {
        try {
            try {
                $readModel = $this->getIdentityProfile->process(
                    new GetIdentityProfileInput(new IdentityIdentifier($request->identityIdentifier())),
                );
            } catch (IdentityNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('identity_not_found', $request->language()), previous: $e);
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($readModel->toArray(), Response::HTTP_OK);
    }
}
