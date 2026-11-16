<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetAuthorizationRequest;

use Source\Identity\Application\Service\PendingAuthorization;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

readonly class AuthorizationRequestReadModel
{
    public function __construct(
        private PendingAuthorization $pending,
        private string $clientName,
    ) {
    }

    /** @return array{requestIdentifier: string, expiresAt: string, clientName: string, scopes: list<array{scope: string, description: string}>} */
    public function toArray(): array
    {
        return [
            'requestIdentifier' => $this->pending->identifier,
            'expiresAt' => $this->pending->expiresAt->format(DATE_ATOM),
            'clientName' => $this->clientName,
            'scopes' => array_map(static fn (AuthorizationScope $scope): array => ['scope' => $scope->value, 'description' => match ($scope) {
                AuthorizationScope::WikiRead => 'Wiki の閲覧',
                AuthorizationScope::DraftRead => 'Draft Wiki の閲覧',
                AuthorizationScope::DraftWrite => 'Draft Wiki の作成・編集',
                AuthorizationScope::DraftSubmit => 'Draft Wiki の提出',
                AuthorizationScope::ImageRead => 'Wiki 画像の閲覧',
                AuthorizationScope::ImageWrite => 'Wiki 画像の追加・編集',
            }], $this->pending->parameters->requestedScopes),
        ];
    }
}
