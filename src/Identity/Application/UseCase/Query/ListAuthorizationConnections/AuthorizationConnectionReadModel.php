<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\ListAuthorizationConnections;

readonly class AuthorizationConnectionReadModel
{
    /** @param list<string> $scopes */
    public function __construct(
        public string $consentIdentifier,
        public string $clientIdentifier,
        public string $clientName,
        public array $scopes,
        public string $createdAt,
        public ?string $revokedAt,
    ) {
    }

    /** @return array{consentIdentifier: string, clientIdentifier: string, clientName: string, scopes: list<string>, createdAt: string, revokedAt: ?string} */
    public function toArray(): array
    {
        return [
            'consentIdentifier' => $this->consentIdentifier,
            'clientIdentifier' => $this->clientIdentifier,
            'clientName' => $this->clientName,
            'scopes' => $this->scopes,
            'createdAt' => $this->createdAt,
            'revokedAt' => $this->revokedAt,
        ];
    }
}
