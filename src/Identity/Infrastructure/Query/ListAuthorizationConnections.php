<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Query;

use Application\Models\Identity\AuthorizationConnection;
use LogicException;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\AuthorizationConnectionReadModel;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\ListAuthorizationConnectionsInputPort;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\ListAuthorizationConnectionsInterface;

readonly class ListAuthorizationConnections implements ListAuthorizationConnectionsInterface
{
    public function process(ListAuthorizationConnectionsInputPort $input): array
    {
        return array_values(AuthorizationConnection::query()
            ->with('client:id,name')
            ->where('identity_id', (string) $input->identityIdentifier())
            ->orderBy('consented_at')->orderBy('id')
            ->get()
            ->map(static fn (AuthorizationConnection $connection): AuthorizationConnectionReadModel => new AuthorizationConnectionReadModel(
                $connection->id,
                $connection->client_id,
                ($connection->client ?? throw new LogicException('Authorization client missing.'))->name,
                $connection->scopes,
                $connection->consented_at->toIso8601String(),
                $connection->revoked_at?->toIso8601String(),
            ))->all());
    }
}
