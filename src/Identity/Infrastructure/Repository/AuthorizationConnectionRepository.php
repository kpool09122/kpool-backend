<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Application\Models\Identity\AuthorizationConnection as ConnectionModel;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationConnectionRepository implements AuthorizationConnectionRepositoryInterface
{
    public function save(AuthorizationConnection $authorizationConnection): void
    {
        // Consent is immutable; revocation is the only update supported by this repository.
        ConnectionModel::query()->forceCreate([
            'id' => $authorizationConnection->identifier,
            'identity_id' => (string) $authorizationConnection->identityIdentifier,
            'client_id' => $authorizationConnection->clientIdentifier,
            'scopes' => $authorizationConnection->scopes,
            'targets' => array_map(static fn (AuthorizationTarget $target): array => [
                'account_id' => (string) $target->accountIdentifier,
                'delegation_id' => $target->delegationIdentifier === null ? null : (string) $target->delegationIdentifier,
            ], $authorizationConnection->targets),
            'original_account_id' => (string) $authorizationConnection->originalAccountIdentifier,
            'original_principal_id' => (string) $authorizationConnection->originalPrincipalIdentifier,
            'initial_account_id' => (string) $authorizationConnection->initialAccountIdentifier,
            'resource' => $authorizationConnection->resource,
            'consented_at' => $authorizationConnection->consentedAt,
            'revoked_at' => $authorizationConnection->revokedAt,
        ]);
    }

    public function find(string $identifier): ?AuthorizationConnection
    {
        $model = ConnectionModel::query()->find($identifier);
        if ($model === null) {
            return null;
        }

        return new AuthorizationConnection(
            $model->id,
            new IdentityIdentifier($model->identity_id),
            $model->client_id,
            $model->scopes,
            array_map(static fn (array $target): AuthorizationTarget => new AuthorizationTarget(
                new AccountIdentifier($target['account_id']),
                $target['delegation_id'] === null ? null : new DelegationIdentifier($target['delegation_id']),
            ), $model->targets),
            new AccountIdentifier($model->original_account_id),
            new PrincipalIdentifier($model->original_principal_id),
            new AccountIdentifier($model->initial_account_id),
            $model->resource,
            $model->consented_at->toDateTimeImmutable(),
            $model->revoked_at?->toDateTimeImmutable(),
        );
    }

    public function revoke(string $identifier): void
    {
        ConnectionModel::query()->whereKey($identifier)->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }
}
