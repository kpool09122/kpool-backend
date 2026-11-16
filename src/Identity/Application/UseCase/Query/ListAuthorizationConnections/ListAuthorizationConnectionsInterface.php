<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\ListAuthorizationConnections;

interface ListAuthorizationConnectionsInterface
{
    /** @return list<AuthorizationConnectionReadModel> */
    public function process(ListAuthorizationConnectionsInputPort $input): array;
}
