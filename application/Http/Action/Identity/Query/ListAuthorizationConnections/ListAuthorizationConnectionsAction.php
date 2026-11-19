<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Query\ListAuthorizationConnections;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\AuthorizationConnectionReadModel;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\ListAuthorizationConnectionsInput;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\ListAuthorizationConnectionsInterface;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListAuthorizationConnectionsAction
{
    public function __construct(
        private ListAuthorizationConnectionsInterface $listAuthorizationConnections,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {
            $connections = $this->listAuthorizationConnections->process(new ListAuthorizationConnectionsInput($this->actorContext->identityIdentifier));
        } catch (StepUpAuthenticationRequiredException $exception) {
            $httpException = new UnauthorizedHttpException(
                detail: error_message('recent_authentication_required', $this->actorContext->language->value),
                extensions: ['code' => 'recent_authentication_required'],
                previous: $exception,
            );

            return response()->json($httpException->toProblemDetails(), $httpException->getHttpStatus(), ['Cache-Control' => 'no-store']);
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $this->actorContext->language->value), previous: $exception);
        }

        return response()->json([
            'connections' => array_map(static fn (AuthorizationConnectionReadModel $connection): array => $connection->toArray(), $connections),
        ], Response::HTTP_OK, ['Cache-Control' => 'no-store']);
    }
}
