<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Query\GetAuthenticatedIdentity;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInput;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetAuthenticatedIdentityAction
{
    public function __construct(
        private GetAuthenticatedIdentityInterface $getAuthenticatedIdentity,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(): JsonResponse
    {
        try {
            try {
                $readModel = $this->getAuthenticatedIdentity->process(
                    new GetAuthenticatedIdentityInput($this->actorContext->identityIdentifier),
                );
            } catch (IdentityNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('identity_not_found', $this->actorContext->language->value), previous: $e);
            }
        } catch (NotFoundHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($readModel->toArray(), Response::HTTP_OK);
    }
}
