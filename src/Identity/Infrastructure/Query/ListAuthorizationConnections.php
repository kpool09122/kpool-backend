<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Query;

use Application\Models\Identity\AuthorizationConsent;
use LogicException;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\AuthorizationConnectionReadModel;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\ListAuthorizationConnectionsInputPort;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\ListAuthorizationConnectionsInterface;

readonly class ListAuthorizationConnections implements ListAuthorizationConnectionsInterface
{
    public function process(ListAuthorizationConnectionsInputPort $input): array
    {
        return array_values(AuthorizationConsent::query()
            ->with('client:id,name')
            ->where('identity_id', (string) $input->identityIdentifier())
            ->orderBy('consented_at')->orderBy('id')
            ->get()
            ->map(static fn (AuthorizationConsent $consent): AuthorizationConnectionReadModel => new AuthorizationConnectionReadModel(
                $consent->id,
                $consent->client_id,
                ($consent->client ?? throw new LogicException('Authorization client missing.'))->name,
                $consent->scopes,
                $consent->consented_at->toIso8601String(),
                $consent->revoked_at?->toIso8601String(),
            ))->all());
    }
}
