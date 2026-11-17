<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Application\Models\Identity\AuthorizationConnection as ConnectionModel;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationConnectionRepository implements AuthorizationConnectionRepositoryInterface
{
    public function save(AuthorizationConnection $authorizationConnection): void
    {
        ConnectionModel::query()->updateOrCreate(
            ['id' => (string) $authorizationConnection->identifier()],
            [
                'identity_id' => (string) $authorizationConnection->identityIdentifier(),
                'client_id' => (string) $authorizationConnection->clientIdentifier(),
                'scopes' => array_map(static fn (AuthorizationScope $scope): string => $scope->value, $authorizationConnection->scopes()),
                'original_account_id' => (string) $authorizationConnection->originalAccountIdentifier(),
                'original_principal_id' => (string) $authorizationConnection->originalPrincipalIdentifier(),
                'resource' => $authorizationConnection->resource(),
                'consented_at' => $authorizationConnection->consentedAt(),
                'revoked_at' => $authorizationConnection->revokedAt(),
            ],
        );
    }

    public function find(AuthorizationConnectionIdentifier $identifier): ?AuthorizationConnection
    {
        $model = ConnectionModel::query()->find((string) $identifier);
        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): array
    {
        return array_values(ConnectionModel::query()->where('identity_id', (string) $identityIdentifier)->orderBy('id')->get()
            ->map(fn (ConnectionModel $model): AuthorizationConnection => $this->toEntity($model))->all());
    }

    private function toEntity(ConnectionModel $model): AuthorizationConnection
    {
        return new AuthorizationConnection(
            new AuthorizationConnectionIdentifier($model->id),
            new IdentityIdentifier($model->identity_id),
            new OAuthClientIdentifier($model->client_id),
            array_map(AuthorizationScope::from(...), $model->scopes),
            new AccountIdentifier($model->original_account_id),
            new AccountPrincipalIdentifier($model->original_principal_id),
            $model->resource,
            $model->consented_at->toDateTimeImmutable(),
            $model->revoked_at?->toDateTimeImmutable(),
        );
    }
}
