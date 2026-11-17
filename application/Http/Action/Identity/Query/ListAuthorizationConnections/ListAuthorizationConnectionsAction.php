<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Query\ListAuthorizationConnections;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\AuthorizationConnectionReadModel;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\ListAuthorizationConnectionsInput;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\ListAuthorizationConnectionsInterface;
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
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $this->actorContext->language->value), previous: $exception);
        }

        return response()->json([
            'connections' => array_map(static fn (AuthorizationConnectionReadModel $connection): array => $connection->toArray(), $connections),
        ], Response::HTTP_OK, ['Cache-Control' => 'no-store']);
    }
}
