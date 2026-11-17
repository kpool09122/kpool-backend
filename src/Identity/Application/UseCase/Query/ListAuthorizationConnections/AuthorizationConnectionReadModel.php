<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\ListAuthorizationConnections;

readonly class AuthorizationConnectionReadModel
{
    /** @param list<string> $scopes */
    public function __construct(
        public string $connectionIdentifier,
        public string $clientIdentifier,
        public string $clientName,
        public array $scopes,
        public string $createdAt,
        public ?string $revokedAt,
    ) {
    }

    /** @return array{connectionIdentifier: string, clientIdentifier: string, clientName: string, scopes: list<string>, createdAt: string, revokedAt: ?string} */
    public function toArray(): array
    {
        return [
            'connectionIdentifier' => $this->connectionIdentifier,
            'clientIdentifier' => $this->clientIdentifier,
            'clientName' => $this->clientName,
            'scopes' => $this->scopes,
            'createdAt' => $this->createdAt,
            'revokedAt' => $this->revokedAt,
        ];
    }
}
